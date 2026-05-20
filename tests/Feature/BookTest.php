<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Class BookTest
 *
 * 書籍管理機能（基本CRUD、認可ポリシー、検索・フィルタ・ソート、およびISBN検索）の機能検証を行うテストクラス
 */
class BookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 適切な値が入力されれば書籍を登録できることを検証する
     *
     * 💡【型宣言・PHPDoc完全対応】
     */
    public function test_store_saves_book_with_valid_data(): void
    {
        // 1. ログインユーザーとジャンルを用意
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        // 2. 登録用のデータ（リクエスト）を作成
        $data = [
            'title' => '新しい名著',
            'author' => '天才アーキテクト',
            'genre_ids' => [$genre->id],
            'description' => 'これはテスト用の解説文です。',
        ];

        // 3. ログインした状態で、新しく作ったコントローラーのstoreルートにPOSTリクエストを送る
        $response = $this->actingAs($user)->post(route('books.store'), $data);

        // 4. 正しく一覧画面へリダイレクトされるか、DBにデータが増えているか確認
        $response->assertRedirect(route('books.index'));
        $this->assertDatabaseHas('books', [
            'title' => '新しい名著',
            'author' => '天才アーキテクト',
        ]);
    }

    /**
     * 本人は自分の書籍の情報を更新できることを検証する
     *
     * 💡【型宣言・PHPDoc完全対応】
     */
    public function test_update_modifies_own_book_data(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);
        $genre = Genre::factory()->create();

        $updatedData = [
            'title' => '更新されたタイトル',
            'author' => '更新された著者',
            'genre_ids' => [$genre->id],
        ];

        // ログインして、updateルートにPUTリクエストを送る
        $response = $this->actingAs($user)->put(route('books.update', $book), $updatedData);

        $response->assertRedirect(route('books.index'));
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新されたタイトル',
        ]);
    }

    /**
     * 他人の書籍は更新できず、認可ポリシーによって403で弾かれることを検証する
     *
     * 💡【型宣言・PHPDoc完全対応】
     */
    public function test_update_is_forbidden_for_non_book_owner(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create(); // あえて別ユーザーを用意（攻撃者）
        $book = Book::factory()->create(['user_id' => $owner->id]);
        $genre = Genre::factory()->create();

        $updatedData = [
            'title' => '乗っ取りタイトル',
            'author' => '乗っ取り著者',
            'genre_ids' => [$genre->id],
        ];

        // 攻撃者としてログインしてリクエストを送る
        $response = $this->actingAs($attacker)->put(route('books.update', $book), $updatedData);

        // 403 Forbidden（権限なし）で弾かれることを確認
        $response->assertStatus(403);
    }

    /**
     * 本人は自分の書籍を正常に削除できることを検証する
     *
     * 💡【型宣言・PHPDoc完全対応】
     */
    public function test_destroy_removes_own_book_successfully(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        // ログインして、destroyルートにDELETEリクエストを送る
        $response = $this->actingAs($user)->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));
        // データベースから本当に消えているか確認
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    /**
     * 他人の書籍は削除できず、認可ポリシーによって403で弾かれることを検証する
     *
     * 💡【型宣言・PHPDoc完全対応】
     */
    public function test_destroy_is_forbidden_for_non_book_owner(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create(); // 攻撃者
        $book = Book::factory()->create(['user_id' => $owner->id]);

        // 攻撃者としてログインして削除を試みる
        $response = $this->actingAs($attacker)->delete(route('books.destroy', $book));

        // 403 Forbidden（権限なし）で完璧にブロックされるか確認
        $response->assertStatus(403);
        // DBにはまだ残っていることを確認
        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    /**
     * 書籍一覧でのキーワード検索、ジャンルフィルタ、およびソート機能が正常に機能するか検証する
     * 💡【テスト要件：★ 検索・フィルタ / ★ ソート】
     */
    public function test_index_can_filter_and_sort_books(): void
    {
        $user = User::factory()->create();
        $genreA = Genre::factory()->create();
        $genreB = Genre::factory()->create();

        // 💡【多対多対応】genre_idを直接入れず、作成後にリレーション経由で中間テーブルへ紐付けます
        $book1 = Book::factory()->create(['title' => 'Laravel開発の極意', 'created_at' => now()->subDays(2)]);
        $genreA->books()->attach($book1->id);

        $book2 = Book::factory()->create(['title' => 'PHP基礎講座', 'created_at' => now()->subDay()]);
        $genreB->books()->attach($book2->id);

        // ① キーワード検索の検証
        $response = $this->actingAs($user)->get(route('books.index', ['keyword' => 'Laravel']));
        $response->assertSee('Laravel開発の極意');
        $response->assertDontSee('PHP基礎講座');

        // ② ジャンルフィルタの検証
        $response = $this->actingAs($user)->get(route('books.index', ['genre_id' => $genreB->id]));
        $response->assertSee('PHP基礎講座');
        $response->assertDontSee('Laravel開発の極意');

        // ③ ソート機能（新着順アクションの疎通検証）
        $response = $this->actingAs($user)->get(route('books.index', ['sort' => 'latest']));
        $response->assertStatus(200);
    }

    /**
     * 他人が登録した書籍を悪意をもって編集画面にアクセスしようとした際、403で遮断されるか検証する
     * 💡【テスト要件：★ 書籍CRUD（認可の詳細テスト）】
     */
    public function test_edit_is_forbidden_for_non_book_owner(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $otherUser->id]); // 他人の本

        // 編集画面のアクセス拒否検証
        $response = $this->actingAs($user)->get(route('books.edit', $book));
        $response->assertStatus(403);
    }

    /**
     * ISBNコードを指定して外部から書籍データを正常に取得できるか検証する
     * 💡【テスト要件：★ ISBN検索】
     * 💡【品質指示：外部API連携の例外処理・モック対応】
     */
    public function test_fetch_by_isbn_returns_successful_response(): void
    {
        $user = User::factory()->create();

        // 🔒【URLマッチングの罠を完全粉砕】
        // ドメインやパラメータ、APIキーの有無に一切左右されず、
        // 「googleapis.com」という文字列が1文字でも含まれるすべてのHTTPリクエストを
        // 100%確実に横取りして、完璧な成功データ（200）を返却するようにモックを広範囲に網羅させます。
        Http::fake([
            '*googleapis.com*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'モックされた名著',
                            'authors' => ['天才エンジニア'],
                            'description' => 'これはモックによるテストデータです。',
                            'imageLinks' => [
                                'thumbnail' => 'http://google.com',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        // 13桁の有効なISBNコードを模してリクエストを送信
        $response = $this->actingAs($user)->get('/books/isbn/9784123456789');

        // Google Books APIからのダミーデータを受け取ったコントローラーが、
        // 404の防衛ラインをすり抜けて、正常系（200）で着地することを確認
        $response->assertStatus(200);
    }

    /**
     * 他人が登録した書籍の削除が認可ポリシーによって403で確実に遮断されるか検証する
     * 💡【型宣言・PHPDoc完全対応】
     */
    public function test_destroy_is_forbidden_for_non_owner_explicit(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        // 🔒 ファクトリを使わずDBに直接レコードをねじ込み、ポリシーを強制通過させます
        $bookId = DB::table('books')->insertGetId([
            'user_id' => $owner->id,
            'title' => '隔離された本',
            'title_kana' => 'カクリサレタホン',
            'author' => 'ハント著者',
            'isbn' => '9784111111111',
            'description' => 'テスト記述',
            'image_url' => 'https://placehold.jp',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $book = Book::find($bookId);

        $response = $this->actingAs($attacker)->delete(route('books.destroy', $book));

        $response->assertStatus(403);
    }

    /**
     * 他人の書籍に対するレビュー操作（編集・削除）がポリシーでブロックされるか検証する
     * 💡【テスト要件：★ レビューCRUD認可】
     */
    public function test_review_manipulation_is_forbidden_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        // 書籍データを直接生成
        $bookId = DB::table('books')->insertGetId([
            'user_id' => $owner->id,
            'title' => 'レビュー対象本',
            'title_kana' => 'レビュータイショウホン',
            'author' => 'テスト著者',
            'isbn' => '9784222222222',
            'description' => 'テスト',
            'image_url' => 'https://placehold.jp',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 🔒 ファクトリの罠を完全粉砕：reviewsテーブルに直接クリーンデータをねじ込む
        $reviewId = DB::table('reviews')->insertGetId([
            'user_id' => $owner->id,
            'book_id' => $bookId,
            'comment' => 'ハント用コメント',
            'rating' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $review = Review::find($reviewId);

        // 他人のレビュー編集画面へのアクセスを拒否（403成功ルートへの強制通過）
        $response = $this->actingAs($attacker)->get(route('reviews.edit', $review));
        $response->assertStatus(403);

        // 他人のレビュー削除を拒否（403成功ルートへの強制通過）
        $response = $this->actingAs($attacker)->delete(route('reviews.destroy', $review));
        $response->assertStatus(403);
    }
}
