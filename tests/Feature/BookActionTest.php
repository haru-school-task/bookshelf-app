<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre; 
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class BookActionTest
 * 
 * 書籍に対するアクション（お気に入り、レビュー投稿、レビューいいね、レビュー編集）の機能検証を行うテストクラス
 *
 * @package Tests\Feature
 */
class BookActionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログインユーザーが書籍のお気に入り登録および解除（トグル処理）を正常に行えるかを検証する
     * デバッグ用の例外ハンドリング無効化コードを完全に除去
     * 
     * @return void
     */
    public function test_authenticated_user_can_toggle_book_favorite(): void
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

    /**
     * ログインユーザーが書籍に対して正常にレビューを投稿できるかを検証する
     * 
     * @return void
     */
    public function test_authenticated_user_can_store_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $reviewData = [
            'rating'  => 5,
            'comment' => '最高の一冊でした！',
        ];

        $response = $this->actingAs($user)->post(route('reviews.store', $book), $reviewData);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reviews', [
            'book_id' => $book->id,
            'user_id' => $user->id,
            'rating'  => 5,
            'comment' => '最高の一冊でした！',
        ]);
    }

    /**
     * ログインユーザーがレビューに対していいねの登録および解除を正常に行えるかを検証する
     * 
     * @return void
     */
    public function test_authenticated_user_can_toggle_review_like(): void
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
            'user_id'   => $user->id,
            'review_id' => $review->id,
        ]);

        // 2. いいね解除
        $this->actingAs($user)->post(route('reviews.like', $review));
        $this->assertDatabaseMissing('review_likes', [
            'user_id'   => $user->id,
            'review_id' => $review->id,
        ]);
    }

    /**
     * レビューの投稿者本人が、自身のレビューを正常に編集・更新できるかを検証する
     * 
     * @return void
     */
    public function test_review_owner_can_update_own_review(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id]);

        $updatedData = ['rating' => 1, 'comment' => 'やっぱり微妙でした'];

        $response = $this->actingAs($user)->put(route('reviews.update', $review), $updatedData);

        $response->assertRedirect(route('books.show', $review->book_id));
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 1]);
    }

    /**
     * レビューの投稿者本人が、自身のレビューを正常に削除（destroy）できるかを検証する
     * 
     * @return void
     */
    public function test_review_owner_can_destroy_own_review(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('reviews.destroy', $review));

        $response->assertRedirect();
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    /**
     * ログイン済みのユーザーが、自身のお気に入り書籍一覧画面を正常に表示できることを検証する
     * 
     * @return void
     */
    public function test_authenticated_user_can_display_favorite_books_index(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        // あらかじめお気に入りに登録しておく
        $user->favoriteBooks()->attach($book->id);

        // お気に入り一覧画面にアクセス
        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertStatus(200);
        $response->assertViewIs('favorites.index');
        $response->assertSee($book->title);
    }

    /**
     * 認証済みの管理者が、ジャンル一覧画面（index）を正常に表示できることを検証する
     * authミドルウェアの追加仕様に適合させ、actingAsによる認証状態を厳密にシミュレート
     * 
     * @return void
     */
    public function test_admin_user_can_display_genres_index(): void
    {
        $admin = User::factory()->create();
        $genre = Genre::factory()->create(['name' => 'SF小説']);

        $response = $this->actingAs($admin)->get(route('genres.index'));

        $response->assertStatus(200);
        $response->assertViewIs('genres.index');
        $response->assertSee('SF小説');
    }

    /**
     * 書籍一覧画面において、キーワード検索およびジャンルによる絞り込みクエリが正常に機能するかを検証する
     * 
     * @return void
     */
    public function test_book_index_can_search_by_keyword_and_filter_by_genre(): void
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

    /**
     * ログインユーザーが、自身の読書統計レポート画面（report）を正常に表示できることを検証する
     * 
     * @return void
     */
    public function test_authenticated_user_can_display_own_reading_report(): void
    {
        $user = User::factory()->create();

        // テストデータを用意（本1冊、レビュー1件を自分に紐づける）
        $book = Book::factory()->create(['user_id' => $user->id]);
        $user->reviews()->create([
            'book_id' => $book->id,
            'rating'  => 5,
            'comment' => 'テストレポート用のレビューです。',
        ]);

        // ログインしてレポート画面へアクセス
        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertViewIs('reports.index');
        $response->assertSee('1');
    }


    /**
     * 書籍一覧画面において、レビュー評点が高い順（降順）に正常にソートされて表示されるかを検証する
     *
     * @return void
     */
    public function test_book_index_can_sort_by_rating_descending(): void
    {
        $book1 = Book::factory()->create(['title' => '普通の本', 'user_id' => User::factory()->create()->id]);
        $book2 = Book::factory()->create(['title' => '超人気本', 'user_id' => User::factory()->create()->id]);

        $user = User::factory()->create();
        $book2->reviews()->create(['user_id' => $user->id, 'rating' => 5, 'comment' => '最高です！']);
        $book1->reviews()->create(['user_id' => $user->id, 'rating' => 1, 'comment' => 'うーん。']);

        $response = $this->get(route('books.index', ['sort' => 'rating']));

        $response->assertStatus(200);
        // 評価の高い「超人気本」が「普通の本」より先に画面に映っている順番を厳密にチェック
        $response->assertSeeInOrder(['超人気本', '普通の本']);
    }

    /**
     * ランキング画面（ranking.index）において、レビューの平均評価が高い順に書籍が正しくランキング表示されるかを検証する
     * 
     * @return void
     */
    public function test_ranking_index_displays_books_ordered_by_average_rating(): void
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
