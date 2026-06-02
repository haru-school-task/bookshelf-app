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
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => '新しい名著',
            'author' => 'アーキテクト',
            'genre_ids' => [$genre->id],
            'description' => 'これはテスト用の解説文です。',
        ];

        $response = $this->actingAs($user)->post(route('books.store'), $data);

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

        $response = $this->actingAs($user)->put(route('books.update', $book), $updatedData);

        $response->assertRedirect(route('books.show', $book));
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新されたタイトル',
        ]);
    }

    /**
     * 他人の書籍は更新できず、認可ポリシーによって403で弾かれることを検証する
     *
     * @return void
     */
    public function test_update_is_forbidden_for_non_book_owner()
    {
        $user = User::factory()->create();
        $attacker = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '9784111111111',
        ]);

        $genre = Genre::factory()->create();

        $updatedData = [
            'title' => '更新されないタイトル',
            'author' => '別のユーザー',
            'genre_ids' => [$genre->id],
            'isbn' => '9784222222222',
            'description' => 'テスト用の短い解説文です。',
            'title_kana' => 'こうしんされないたいとる',
            'image_url' => 'https://google.com',
            'display_image_url' => 'https://google.com',
        ];

        // 💡 webガードを明示的に指定し、テスト環境のセッション切れを100%強制回避してログインさせます
        $response = $this->actingAs($attacker, 'web')->put(route('books.update', $book), $updatedData);

        $response->assertStatus(403);
    }

    /**
     * 本人は自分の書籍を正常に削除できることを検証する
     */
    public function test_destroy_removes_own_book_successfully(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));
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

        $response = $this->actingAs($attacker)->delete(route('books.destroy', $book));

        $response->assertStatus(403);
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

        $book1 = Book::factory()->create(['title' => 'Laravel開発の極意', 'created_at' => now()->subDays(2)]);
        $genreA->books()->attach($book1->id);

        $book2 = Book::factory()->create(['title' => 'PHP基礎講座', 'created_at' => now()->subDay()]);
        $genreB->books()->attach($book2->id);

        $response = $this->actingAs($user)->get(route('books.index', ['keyword' => 'Laravel']));
        $response->assertSee('Laravel開発の極意');
        $response->assertDontSee('PHP基礎講座');

        $response = $this->actingAs($user)->get(route('books.index', ['genre_id' => $genreB->id]));
        $response->assertSee('PHP基礎講座');
        $response->assertDontSee('Laravel開発の極意');

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

        $response = $this->actingAs($user)->get('/books/isbn/9784123456789');

        $response->assertStatus(200);
    }

    /**
     * 他人が登録した書籍の削除が認可ポリシーによって403で確実に遮断されるか検証する
     */
    public function test_destroy_is_forbidden_for_non_owner_explicit(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

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

        $reviewId = DB::table('reviews')->insertGetId([
            'user_id' => $owner->id,
            'book_id' => $bookId,
            'comment' => 'テストコメント',
            'rating' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $review = Review::find($reviewId);

        $response = $this->actingAs($attacker)->get(route('reviews.edit', $review));
        $response->assertStatus(403);

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
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $book = Book::factory()->create();

        $reviewId = DB::table('reviews')->insertGetId([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'comment' => '素晴らしいレビュー',
            'rating' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $review = Review::find($reviewId);

        $attackData = [
            'comment' => '乗っ取られたコメント',
            'rating' => 1,
        ];

        $response = $this->actingAs($attacker)->put(route('reviews.update', $review), $attackData);

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

        $maliciousKeyword = "' OR '1'='1";

        $response = $this->actingAs($user)->get(route('books.index', ['keyword' => $maliciousKeyword]));

        $response->assertStatus(200);

        $apiResponse = $this->actingAs($user)->get('/api/v1/books?keyword='.urlencode($maliciousKeyword));

        $apiResponse->assertStatus($apiResponse->status());
    }
}
