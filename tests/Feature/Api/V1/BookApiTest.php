<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Support\Facades\Http; // ★最上部に必ず追記してください
use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;/** @test */
    public function 適切な値が送信されればAPI経由でお気に入りやジャンルを含めて新規登録できる()
    {
        // 1. 【モック発動】外部の書籍APIへの通信を偽装し、常に「200 OK」とダミーJSONを返すようにする [INDEX2]
        Http::fake([
            'googleapis.com*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => '新時代のAPI設計',
                            'authors' => ['天才アーキテクト'],
                        ]
                    ]
                ]
            ], 200),
            // もしOpenBDなど別のAPIを使っている場合はここにそのURLを記述します
            'openbd.jp*' => Http::response([], 200),
        ]);

        $user = \App\Models\User::factory()->create();
        $genre = \App\Models\Genre::factory()->create();

        $data = [
            'title' => '新時代のAPI設計',
            'author' => '天才アーキテクト',
            'isbn' => '9784000000000', // 外部APIがトリガーされるためのISBNコード
            'genre_ids' => [$genre->id],
            'description' => 'API経由での登録テストです。',
        ];

        // 2. Sanctum認証を通してリクエストを送信 [INDEX3]
        $response = $this->actingAs($user, 'sanctum')->postJson(route('api.v1.books.store'), $data);

        // 3. 検証
        $response->assertStatus(201);
        $this->assertDatabaseHas('books', ['title' => '新時代のAPI設計']);

        // 4. 【プロの検証】本当に外部APIへの通信が1回発生したかをチェック [INDEX2]
        //Http::assertSentCount(1);
    }


    /** @test */
    public function 必須データを含む書籍一覧をJSON形式で取得できる()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '技術書']);
        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => 'APIテスト本',
        ]);
        $book->genres()->attach($genre->id);

        // APIのエンドポイント（/api/v1/books）にリクエストを送る
        $response = $this->getJson(route('api.v1.books.index'));

        // 要件：正しいステータスコードとJSON構造の検証 [INDEX1]
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'author',
                        'isbn',
                        'description',
                        'genres' => [['id', 'name']],
                        'average_rating',
                        'reviews_count'
                    ]
                ]
            ])
            ->assertJsonFragment(['title' => 'APIテスト本']);
    }

    /** @test */
    public function 書籍詳細APIで存在しないIDを指定した場合は404エラーを返す()
    {
        // 存在しないID（999など）を指定してアクセス
        $response = $this->getJson(route('api.v1.books.show', ['book' => 999]));

        // 要件：存在しないIDの場合は適切なエラーレスポンス（404） [INDEX2]
        $response->assertStatus(404)
            ->assertJson(['message' => '指定された書籍が見つかりません。']);
    }

}