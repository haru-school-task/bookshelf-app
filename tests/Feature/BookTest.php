<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Genre;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 適切な値が入力されれば書籍を登録できる()
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
}
