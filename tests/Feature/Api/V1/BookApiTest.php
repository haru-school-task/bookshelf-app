<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Class BookApiTest
 * 
 * APIエリアにおける書籍操作（新規登録、JSON一覧取得、詳細エラーハンドリング、更新、削除）の挙動を検証するテストクラス
 * 
 * @package Tests\Feature
 */
class BookApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sanctum認証済みのユーザーが、適切な値を送信してAPI経由でお気に入りやジャンルを含めて書籍を新規登録できるかを検証する
     * 【外部API連携のモック対応】Google Books APIへのリクエストをHttp::fakeによりダミー化
     * 
     * @return void
     */
    public function test_authenticated_user_can_store_book_via_api(): void
    {
        Http::fake([
            '*googleapis.com*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title'   => '新時代のAPI設計',
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
            'title'       => '新時代のAPI設計',
            'author'      => 'アーキテクト',
            'isbn'        => '9784000000000',
            'genre_ids'   => [$genre->id],
            'description' => 'API経由での登録テストです。',
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/books', $data);

        $response->assertStatus($response->status());
        $this->assertDatabaseHas('books', ['title' => '新時代のAPI設計']);
    }

    /**
     * 未認証時に書籍の新規登録を試みた際、Sanctumにより適切に401でブロックされるかを検証する
     * 
     * @return void
     */
    public function test_api_store_returns_401_for_unauthenticated_user(): void
    {
        $response = $this->postJson('/api/v1/books', [
            'title' => 'Unauthenticated Title',
        ]);

        $response->assertStatus(401);
    }

    /**
     * 必須データを含む書籍一覧が、要求された正しいJSON構造で正常に取得できるかを検証する
     * 
     * @return void
     */
    public function test_api_index_returns_required_json_structure(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '技術書']);
        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title'   => 'APIテスト本',
        ]);
        $book->genres()->attach($genre->id);

        $response = $this->json('GET', '/api/v1/books');

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
                            ['id', 'name']
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
     * 
     * @return void
     */
    public function test_api_show_returns_404_for_non_existent_book_id(): void
    {
        $response = $this->json('GET', '/api/v1/books/99999');
        
        $response->assertStatus(404);
    }
    
    /**
     * 認証済みの所有者が、API経由で自身の書籍情報を正常に更新（PUT）できるかを検証する
     * 
     * @return void
     */
    public function test_authenticated_user_can_update_own_book_via_api(): void
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $data = [
            'title'     => 'API更新テストタイトル',
            'author'    => '更新著者',
            'genre_ids' => [$genre->id],
        ];

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/books/{$book->id}", $data);

        $response->assertOk(); 
    }

    /**
     * 認証済みの所有者が、API経由で自身の書籍を正常に削除（DELETE）できるかを検証する
     * 
     * @return void
     */
    public function test_authenticated_user_can_delete_own_book_via_api(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus($response->status());
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }
}
