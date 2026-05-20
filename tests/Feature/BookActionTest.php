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
        $this->withoutExceptionHandling(); // ← これを追加して再実行！
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
    public function 管理者はジャンル一覧画面を表示できる()
    {
        // 1. テスト用のユーザー（管理者）とジャンルを作成
        $admin = User::factory()->create();
        $genre = Genre::factory()->create(['name' => 'SF小説']);

        // 2. ★修正：actingAs($admin) を追加し、新しく設定したログイン必須の門番をパスさせます [INDEX2]
        $response = $this->actingAs($admin)->get(route('genres.index'));

        $response->assertStatus(200);
        $response->assertViewIs('genres.index');
        $response->assertSee('SF小説');
    }

    /** @test */
    public function 書籍一覧でキーワード検索およびジャンル絞り込みができる()
    {
        $genre1 = Genre::factory()->create(['name' => '小説']);
        $genre2 = Genre::factory()->create(['name' => '技術書']);

        $book1 = Book::factory()->create(['title' => '夏目漱石の本', 'user_id' => User::factory()->create()->id]);
        $book2 = Book::factory()->create(['title' => 'PHPの教科書', 'user_id' => User::factory()->create()->id]);

        $book1->genres()->attach($genre1->id);
        $book2->genres()->attach($genre2->id);

        // 1. キーワード「夏目」で検索リクエストを送る
        $response = $this->get(route('books.index', ['keyword' => '夏目']));
        $response->assertStatus(200);
        $response->assertSee('夏目漱石の本');
        $response->assertDontSee('PHPの教科書');

        // 2. ジャンル「技術書」で絞り込みリクエストを送る
        $response = $this->get(route('books.index', ['genre_id' => $genre2->id]));
        $response->assertStatus(200);
        $response->assertSee('PHPの教科書');
        $response->assertDontSee('夏目漱石の本');
    }

    /** @test */
    public function ログインユーザーは自身の読書統計レポート画面を表示できる()
    {
        $user = User::factory()->create();

        // テストデータを仕込む（本1冊、レビュー1件を自分に紐づける）
        $book = Book::factory()->create(['user_id' => $user->id]);
        $user->reviews()->create([
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'テストレポート用のレビューです。',
        ]);

        // ログインしてレポート画面へアクセス
        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertViewIs('reports.index');
        // 画面に「総レビュー数：1」や「平均評価：5」の形跡があるか確認
        $response->assertSee('1');
    }

    /** @test */
    public function 書籍一覧で評点が高い順にソートして表示できる()
    {
        $book1 = Book::factory()->create(['title' => '普通の本', 'user_id' => User::factory()->create()->id]);
        $book2 = Book::factory()->create(['title' => '超人気本', 'user_id' => User::factory()->create()->id]);

        $user = User::factory()->create();
        $book2->reviews()->create(['user_id' => $user->id, 'rating' => 5, 'comment' => '最高です！']);
        $book1->reviews()->create(['user_id' => $user->id, 'rating' => 1, 'comment' => 'うーん。']);

        $response = $this->get(route('books.index', ['sort' => 'rating']));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['超人気本', '普通の本']);
    }

    /** @test */
    public function 書籍がレビュー平均評価が高い順にランキング表示される()
    {
        $popularBook = Book::factory()->create(['title' => '人気本', 'user_id' => User::factory()->create()->id]);
        $normalBook = Book::factory()->create(['title' => '普通本', 'user_id' => User::factory()->create()->id]);

        $users = User::factory(3)->create();

        $popularBook->reviews()->create(['user_id' => $users[0]->id, 'rating' => 5, 'comment' => '最高！']);
        $popularBook->reviews()->create(['user_id' => $users[1]->id, 'rating' => 5, 'comment' => '神著！']);
        $normalBook->reviews()->create(['user_id' => $users[2]->id, 'rating' => 3, 'comment' => '普通。']);

        $response = $this->get(route('ranking.index'));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['人気本', '普通本']);
    }
}
