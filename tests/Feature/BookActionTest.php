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

}
