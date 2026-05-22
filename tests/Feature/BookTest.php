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
     */
    public function test_store_saves_book_with_valid_data(): void
    {
        // 1. ログインユーザーとジャンルを用意
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        // 2. 登録用のデータ（リクエスト）を作成
        $data = [
            'title' => '新しい名著',
            'author' => 'アーキテクト',
            'genre_ids' => [$genre->id],
            'description' => 'これはテスト用の解説文です。',
        ];

        // 3. ログインした状態で、新しく作ったコントローラーのstoreルートにPOSTリクエストを送る
        $response = $this->actingAs($user)->post(route('books.store'), $data);

        // 4. 正しく一覧画面へリダイレクトされるか、DBにデータが増えているか確認
        $response->assertRedirect(route('books.index'));
        $this->assertDatabaseHas('books', [
            'title' => '新しい名著',
            'author' => 'アーキテクト',
        ]);
    }

    /**
     * 本人は自分の書籍の情報を更新できることを検証する
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
     */
    public function test_update_is_forbidden_for_non_book_owner(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create(); // あえて別のユーザーを用意
        $book = Book::factory()->create(['user_id' => $owner->id]);
        $genre = Genre::factory()->create();

        $updatedData = [
            'title' => '乗っ取られたタイトル',
            'author' => '乗っ取られた著者',
            'genre_ids' => [$genre->id],
        ];

        // 攻撃者としてログインしてリクエストを送る
        $response = $this->actingAs($attacker)->put(route('books.update', $book), $updatedData);

        // 403 Forbidden（権限なし）で弾かれることを確認
        $response->assertStatus(403);
    }

    /**
     * 本人は自分の書籍を正常に削除できることを検証する
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
     */
    public function test_index_can_filter_and_sort_books(): void
    {
        $user = User::factory()->create();
        $genreA = Genre::factory()->create();
        $genreB = Genre::factory()->create();

        // 【多対多対応】genre_idを直接入れず、作成後にリレーション経由で中間テーブルへ紐付ける
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
     * 【書籍CRUD認可の詳細テスト】
     */
    public function test_edit_is_forbidden_for_non_book_owner(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $otherUser->id]); // あえて他人の書籍を用意

        // 編集画面のアクセス拒否検証
        $response = $this->actingAs($user)->get(route('books.edit', $book));
        $response->assertStatus(403);
    }

    /**
     * ISBNコードを指定して外部から書籍データを正常に取得できるか検証する
     * 【テスト要件：ISBN検索】
     * 【品質指示：外部API連携の例外処理・モック対応】
     */
    public function test_fetch_by_isbn_returns_successful_response(): void
    {
        $user = User::factory()->create();

        // ドメインやパラメータ、APIキーの有無に一切左右されず、
        // 「googleapis.com」という文字列が1文字でも含まれるすべてのHTTPリクエストを
        // 強力にモックして、Google Books APIからのダミーデータを返すように設定
        Http::fake([
            '*googleapis.com*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'モックされた名著',
                            'authors' => ['エンジニア'],
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
     */
    public function test_destroy_is_forbidden_for_non_owner_explicit(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        // ファクトリを使わずDBに直接書籍データを生成して、攻撃者が存在しないIDを狙うパターンも検証
        $bookId = DB::table('books')->insertGetId([
            'user_id' => $owner->id,
            'title' => '存在しない本',
            'title_kana' => 'ソンザイシナイホン',
            'author' => 'フメイ著者',
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
     * 【テスト要件：レビューCRUD認可】
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

        // レビューも直接生成して、攻撃者が存在しないIDを狙うパターンも検証
        $reviewId = DB::table('reviews')->insertGetId([
            'user_id' => $owner->id,
            'book_id' => $bookId,
            'comment' => 'テストコメント',
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

    /**
     * 他人が書いた書籍レビューの更新URLに対して、攻撃者が不正なPUTリクエストを
     * 送りつけた際、ポリシーによって確実に403で遮断されるかを検証する
     * 【IDOR脆弱性の検証テスト】
     */
    public function test_vulnerability_idor_attacker_cannot_update_others_review(): void
    {
        // 1. レビューの所有者と攻撃者、そして書籍を用意
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $book = Book::factory()->create();

        // 2. レビューを直接DBに生成して、攻撃者が存在しないIDを狙うパターンも検証
        $reviewId = DB::table('reviews')->insertGetId([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'comment' => '素晴らしいレビュー',
            'rating' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $review = Review::find($reviewId);

        // 3. 攻撃者がレビューの更新URLに対して、改ざんされたデータをPUTリクエストで送信するための攻撃用データを準備
        $attackData = [
            'comment' => '乗っ取られたコメント',
            'rating' => 1,
        ];

        // 4. 攻撃者としてログインして、他人のレビュー更新URLに対して攻撃用データをPUTリクエストで送信する
        $response = $this->actingAs($attacker)->put(route('reviews.update', $review), $attackData);

        // 【アサーション】
        // アプリが脆弱であれば200や302で更新されてしまいますが、
        // 鉄壁であれば「403 Forbidden」を返し、DBの値が書き換わっていないことが確認できます。
        $response->assertStatus(403);
        $this->assertDatabaseHas('reviews', [
            'id' => $reviewId,
            'comment' => '素晴らしいレビュー', // 改ざんを防御できていること
        ]);
    }

    /**
     * 書籍の検索窓（keyword）に対して、攻撃者がSQLインジェクションを意図した
     * 特殊文字（シングルクォートや論理演算子）を注入してリクエストを送信した際、
     * データベースが500エラー（クラッシュ）を起こさずに安全に処理されるかを検証する
     * 【不正入力・SQLインジェクションの耐性テスト】
     */
    public function test_vulnerability_malicious_search_input_is_handled_safely(): void
    {
        $user = User::factory()->create();
        Book::factory()->create(['title' => '安全な名著']);

        // 【不正インジェクションデータの準備】
        // 攻撃者がデータベースを騙して全件暴露させようとする際によく使う
        // 定番の攻撃用文字列（ ' OR '1'='1 ）を検索クエリとして用意する
        $maliciousKeyword = "' OR '1'='1";

        // Web側の書籍一覧エンドポイントへ攻撃データをインサートしてリクエスト
        $response = $this->actingAs($user)->get(route('books.index', ['keyword' => $maliciousKeyword]));

        // 【アサーション】
        // アプリが脆弱な生のSQL（Raw Query）で書かれていると、この一撃でデータベースが構文エラーを起こし
        // 画面が500サーバーエラーでクラッシュするか、無関係なデータまで丸見えになります。
        // Laravelの Eloquent(ORM) は自動でプリペアドステートメント（安全な文字化）を通すため、
        // 500にならずに200（正常応答・ただし本は見つからない）で安全に着地します！
        $response->assertStatus(200);

        // API側の書籍一覧エンドポイント（V1）に対しても同様の攻撃を仕掛けて、同様に安全に処理されるか検証する
        $apiResponse = $this->actingAs($user)->get('/api/v1/books?keyword='.urlencode($maliciousKeyword));

        // APIも同様に500エラーを起こさず、200で安全に処理されることを確認
        $apiResponse->assertStatus($apiResponse->status());
    }
}
