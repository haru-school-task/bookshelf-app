<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class GenreTest
 *
 * ジャンル管理機能（詳細画面の10件ページネーション、書籍紐付き時の削除制限を含む）を検証するテストクラス
 * 
 * @package Tests\Feature
 */
class GenreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ジャンル一覧画面が正常に表示されることを検証する
     * 
     * @return void
     */
    public function test_index_displays_genres(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '技術書']);

        $response = $this->actingAs($user)->get(route('genres.index'));

        $response->assertStatus(200);
        $response->assertViewHas('genres');
    }

    /**
     * ジャンル詳細画面が正常に表示され、紐づく書籍がページネーション（10件）で渡されることを検証する
     * 仕様書要件：詳細画面のページネーション10件/ページ
     * 
     * @return void
     */
    public function test_show_displays_genre_detail_with_ten_books_pagination(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        // 💡【多対多対応】本を11冊作成し、モデルのリレーション経由でジャンルへ安全に紐付ける
        // ※ もしモデル内のリレーション名が「books」でない場合は、定義されている名前に合わせる
        Book::factory()->count(11)->create()->each(function (Book $book) use ($genre) {
            $genre->books()->attach($book->id);
        });

        $response = $this->actingAs($user)->get(route('genres.show', $genre));

        $response->assertStatus(200);
        $response->assertViewHas('genre');
        $response->assertViewHas('books', function ($books) {
            return $books instanceof LengthAwarePaginator && $books->perPage() === 10;
        });
    }

    /**
     * ジャンル作成画面が正常に表示されることを検証する
     *  
     * @return void
     */
    public function test_create_returns_successful_response(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.create'));

        $response->assertStatus(200);
    }

    /**
     * ジャンルがバリデーションを通過して正常に保存できるかを検証する
     * 
     * @return void
     */
    public function test_store_saves_new_genre(): void
    {
        $user = User::factory()->create();
        $postData = ['name' => '小説'];

        $response = $this->actingAs($user)->post(route('genres.store'), $postData);

        $this->assertDatabaseHas('genres', ['name' => '小説']);
        $response->assertRedirect(route('genres.index'));
    }

    /**
     * ジャンル編集画面が正常に表示されることを検証する
     * 
     * @return void
     */
    public function test_edit_returns_successful_response(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.edit', $genre));

        $response->assertStatus(200);
        $response->assertViewHas('genre');
    }

    /**
     * ジャンルの更新が正常に動作するかを検証する
     *  
     * @return void
     */
    public function test_update_modifies_genre(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '古い名前']);
        $putData = ['name' => '新しい名前'];

        $response = $this->actingAs($user)->put(route('genres.update', $genre), $putData);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '新しい名前',
        ]);
        $response->assertRedirect(route('genres.index'));
    }

    /**
     * 書籍の紐付きがないクリーンなジャンルは正常に削除できることを検証する
     *  
     * @return void
     */
    public function test_destroy_removes_genre_without_books(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->delete(route('genres.destroy', $genre));

        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
        $response->assertRedirect(route('genres.index'));
    }

    /**
     * 書籍が登録されているジャンルを削除しようとした際、ブロックされる（制限される）ことを検証する
     *  
     * @return void
     */
    public function test_destroy_restricts_deletion_if_genre_has_books(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        // 【多対多対応】本を1冊作成し、モデルのリレーション経由でジャンルへ安全に紐付ける
        $book = Book::factory()->create();
        $genre->books()->attach($book->id);

        // 削除を実行
        $response = $this->actingAs($user)->delete(route('genres.destroy', $genre));

        // 【アサーション】削除が制限され、データベースにジャンルが残っていることを確認
        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
    }
}
