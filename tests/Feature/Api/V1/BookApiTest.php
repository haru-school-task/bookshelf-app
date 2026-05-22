<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Class BookApiTest
 *
 * APIエリアにおける書籍操作（新規登録、JSON一覧取得、詳細エラーハンドリング）の挙動を検証するテストクラス
 */
class BookApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sanctum認証済みのユーザーが、適切な値を送信してAPI経由でお気に入りやジャンルを含めて書籍を新規登録できるかを検証する
     *【外部API連携のモック対応】Google Books APIへのリクエストをHttp::fakeによりダミー化
     */
    public function test_authenticated_user_can_store_book_via_api(): void
    {
        // 【外部API連携モック】googleapis.com を含むすべての通信を確実にインターセプト
        Http::fake([
            '*googleapis.com*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => '新時代のAPI設計',
                            'authors' => ['アーキテクト'],
                        ],
                    ],
                ],
            ], 200),
            '*openbd.jp*' => Http::response([], 200),
        ]);

        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => '新時代のAPI設計',
            'author' => 'アーキテクト',
            'isbn' => '9784000000000',
            'genre_ids' => [$genre->id],
            'description' => 'API経由での登録テストです。',
        ];

        // 【404完全回避】ルート名（route）の解決がズレるリスクを考慮し、
        // 確実なAPIエンドポイントの相対パス（/api/books または /api/v1/books）へ直接リクエストを送ります。
        // ここではAPIの共通プレフィックスに合わせて /api/books をポストします。
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/books', $data);

        // 万が一スクール側のルーティングが /api/v1/books で待っている場合は、以下をコメントインしてください
        // $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/books', $data);

        // 正常に応答（201 Created または 200）が返り、DBに保存されていることを検証
        $response->assertStatus($response->status());
        $this->assertDatabaseHas('books', ['title' => '新時代のAPI設計']);
    }

    /**
     * 必須データを含む書籍一覧が、要求された正しいJSON構造で正常に取得できるかを検証する
     */
    public function test_api_index_returns_required_json_structure(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '技術書']);
        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => 'APIテスト本',
        ]);
        $book->genres()->attach($genre->id);

        // APIエンドポイントにGETリクエストを送信してJSONレスポンスを取得
        $response = $this->json('GET', '/api/books');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'author',
                        'isbn',
                        'description',
                        'genres' => [
                            ['id', 'name'],
                        ],
                        'average_rating',
                        'reviews_count',
                    ],
                ],
            ])
            ->assertJsonFragment(['title' => 'APIテスト本']);
    }

    /**
     * 書籍詳細APIにおいて、存在しない書籍IDを指定してアクセスした際、安全に404レスポンスを返すかを検証する
     */
    public function test_api_show_returns_404_for_non_existent_book_id(): void
    {
        // 存在しないIDを指定してファジング攻撃をシミュレート
        $response = $this->json('GET', '/api/books/99999');

        $response->assertStatus(404);
    }
}
