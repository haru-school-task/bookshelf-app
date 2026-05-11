<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookAccessTest extends TestCase
{
    use RefreshDatabase; // テストごとにDBをリセットして綺麗に保つ魔法
    /** @test */
    public function 一覧画面にアクセスできる()
    {
        $response = $this->get(route('books.index'));

        $response->assertStatus(200);
        $response->assertViewIs('books.index');
    }

    /** @test */
    public function 詳細画面にアクセスできる()
    {
        $this->withoutExceptionHandling(); // ← これを追加して再実行！
        // テスト用のデータを1件作成
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertViewIs('books.show');
        $response->assertSee($book->title); // 画面にタイトルが表示されているか
    }
}
