<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class BookTest
 *
 * APIエリアにおける書籍データのアクセス制御および情報隔離を検証するテストクラス
 * 
 * @package Tests\Feature
 */
class BookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * API経由で書籍一覧（index）をリクエストした際、
     * 正常に200が返り、かつ他人の非公開データが意図せず漏洩していないかを検証する
     * 
     * @return void
     */
    public function test_api_index_returns_successful_json_response(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['title' => 'API公開本']);

        // 【アクセス制御】APIエンドポイントは /v1 を削除して、/api/books に直接アクセス
        $response = $this->json('GET', '/api/books');

        // 【アサーション】500クラッシュを起こさず、正常にJSON構造で書籍が返却されるか検証
        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => 'API公開本']);
    }

    /**
     * 特定の書籍詳細API（show）に対して、
     * 存在しないIDを指定して不正に揺さぶった際、500エラーを出さずに安全に404を返すかを検証する
     * 【例外ハンドリングのハントテスト】
     * 
     * @return void
     */
    public function test_api_show_returns_404_safely_for_non_existent_book(): void
    {
        $user = User::factory()->create();

        // こちらも同様に /v1 を削除して、存在しないID「99999」を直接指定してアクセス
        $response = $this->actingAs($user)->json('GET', '/api/books/99999');

        // 安全に404を返すか検証
        $response->assertStatus(404);
    }
}
