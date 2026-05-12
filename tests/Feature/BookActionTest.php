<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookActionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ログインユーザーは書籍をお気に入りに登録および解除できる()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        // 1. お気に入り登録（1回目のリクエスト）
        $response = $this->actingAs($user)->post(route('favorites.toggle', $book));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // 2. お気に入り解除（2回目のリクエストで削除されるか確認）
        $response = $this->actingAs($user)->post(route('favorites.toggle', $book));

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    /** @test */
    public function ログインユーザーは書籍にレビューを投稿できる()
    {
        $this->withoutExceptionHandling(); // ← これを追加！
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $reviewData = [
            'rating' => 5,
            'comment' => '最高の一冊でした！',
        ];

        $response = $this->actingAs($user)->post(route('reviews.store', $book), $reviewData);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reviews', [
            'book_id' => $book->id,
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => '最高の一冊でした！',
        ]);
    }

    /** @test */
    public function ログインユーザーはレビューにいいねを登録および解除できる()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        // テスト用のレビューを作成
        $review = Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => User::factory()->create()->id, // レビュー投稿者は別の人
        ]);

        // 1. いいね登録
        $this->actingAs($user)->post(route('reviews.like', $review));
        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        // 2. いいね解除
        $this->actingAs($user)->post(route('reviews.like', $review));
        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    /** @test */
    public function 本人は自分のレビューを編集・更新できる()
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id]);

        $updatedData = ['rating' => 1, 'comment' => 'やっぱり微妙でした'];

        $response = $this->actingAs($user)->put(route('reviews.update', $review), $updatedData);

        $response->assertRedirect(route('books.show', $review->book_id));
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 1]);
    }

    /** @test */
    public function 本人は自分のレビューを削除できる()
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('reviews.destroy', $review));

        $response->assertRedirect();
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    /** @test */
    public function ログインユーザーは自分のお気に入り書籍一覧を表示できる()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        // あらかじめお気に入りに登録しておく
        $user->favoriteBooks()->attach($book->id);

        // お気に入り一覧画面にアクセス
        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertStatus(200);
        $response->assertViewIs('favorites.index');
        $response->assertSee($book->title); // 登録した本のタイトルが見えるか
    }

    /** @test */
    public function 書籍がお気に入り数が多い順にランキング表示される()
    {
        $this->withoutExceptionHandling(); // ← これを追加して再実行！
        // 1. 2冊の本を用意
        $popularBook = Book::factory()->create(['title' => '人気本']);
        $normalBook = Book::factory()->create(['title' => '普通本']);

        // 2. 人気本に2人、普通本に1人のお気に入りを付ける
        $users = User::factory(3)->create();
        $popularBook->favoriteUsers()->attach([$users[0]->id, $users[1]->id]);
        $normalBook->favoriteUsers()->attach([$users[2]->id]);

        // 3. ランキング画面にアクセス
        $response = $this->get(route('ranking.index'));

        $response->assertStatus(200);
        // 4. 人気本が「先」に表示されていることを確認（順序のチェック）
        $response->assertSeeInOrder(['人気本', '普通本']);
    }

    /** @test */
    public function 管理者はジャンル一覧画面を表示できる()
    {
        $this->withoutExceptionHandling(); // ← これを追加して再実行！
        $genre = Genre::factory()->create(['name' => 'SF小説']);

        $response = $this->get(route('genres.index'));

        $response->assertStatus(200);
        $response->assertViewIs('genres.index');
        $response->assertSee('SF小説');
    }



}
