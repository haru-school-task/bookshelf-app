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

    /** @test */
    public function 本人は自分の書籍の情報を更新できる()
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

    /** @test */
    public function 他人の書籍は更新できずエラーになる()
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

        // 403 Forbidden（権限なし）で弾かれることを確認（あなたが作ったPolicyの強度の証明！）
        $response->assertStatus(403);
    }

    /** @test */
    public function 本人は自分の書籍を削除できる()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        // ログインして、destroyルートにDELETEリクエストを送る
        $response = $this->actingAs($user)->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));
        // データベースから本当に消えているか確認
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    /** @test */
    public function 他人の書籍は削除できずエラーになる()
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
}
