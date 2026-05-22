<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class BookAccessTest
 *
 * 書籍一覧画面および詳細画面へのアクセス権限とビューの描画を検証するテストクラス
 */
class BookAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未認証・認証時に関わらず書籍一覧画面（index）に正常にアクセスできることを検証する
     */
    public function test_index_screen_can_be_accessed(): void
    {
        $response = $this->get(route('books.index'));

        $response->assertStatus(200);
        $response->assertViewIs('books.index');
    }

    /**
     * 登録済みの書籍詳細画面（show）に正常にアクセスでき、タイトルが正しく描写されることを検証する
     */
    public function test_show_screen_can_be_accessed(): void
    {
        // 1. テスト用データの準備
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        // 2. 詳細画面へのアクセス検証
        $response = $this->get(route('books.show', $book));

        // 3. アサーション（判定）
        $response->assertStatus(200);
        $response->assertViewIs('books.show');
        $response->assertSee($book->title);
    }
}
