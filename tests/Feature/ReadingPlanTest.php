<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class ReadingPlanTest
 *
 * 読書計画機能（一覧、作成、保存、編集、更新、削除、完了）の機能検証およびコードカバーを行うテストクラス
 */
class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 読書計画一覧画面が正常に表示され、他のユーザーの計画が表示されないことを検証する
     */
    public function test_index_displays_only_authenticated_users_plans(): void
    {
        // 1. テストデータの準備（ログインユーザーと他のユーザー）
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // それぞれのユーザーに紐付く書籍を作成
        $myBook = Book::factory()->create(['title' => '私の愛読書']);
        $otherBook = Book::factory()->create(['title' => '他のユーザーの愛読書']);

        // それぞれに独立した書籍を紐付けて計画を作成
        $myPlan = ReadingPlan::factory()->create(['user_id' => $user->id, 'book_id' => $myBook->id]);
        $otherPlan = ReadingPlan::factory()->create(['user_id' => $otherUser->id, 'book_id' => $otherBook->id]);

        // 2. 認証状態でのアクセス検証
        $response = $this->actingAs($user)->get(route('reading-plans.index'));

        // 3. アサーション（判定）
        $response->assertStatus(200);
        $response->assertSee($myPlan->book->title);

        // 他のユーザーの愛読書が表示されていないことを検証
        $response->assertDontSee($otherPlan->book->title);
    }

    /**
     * 読書計画がバリデーションを通過して正常に保存できるかを検証する
     */
    public function test_store_saves_reading_plan_with_valid_data(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $postData = [
            'book_id' => $book->id,
            'target_date' => now()->addDays(7)->format('Y-m-d'), // 今日以降の日付
        ];

        $response = $this->actingAs($user)->post(route('reading-plans.store'), $postData);

        // データベースに意図したデータが、初期ステータス（Unread）で入っているか確認
        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Unread->value,
        ]);

        $response->assertRedirect(route('reading-plans.index'));
    }

    /**
     * 他のユーザーの読書計画の編集画面を開こうとした際、認可ポリシーによって403拒否されるか検証する
     */
    public function test_edit_is_forbidden_for_non_owner(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        // 他のユーザーが作成した読書計画
        $otherPlan = ReadingPlan::factory()->create(['user_id' => $otherUser->id, 'book_id' => $book->id]);

        // 自分が他のユーザーの編集URLにGETリクエストを送信した時、403 Forbiddenになるか確認
        $response = $this->actingAs($user)->get(route('reading-plans.edit', $otherPlan));

        $response->assertStatus(403);
    }

    /**
     * 読書計画の完了アクションが正常に動作し、ステータスが更新されるか検証する
     */
    public function test_complete_action_updates_plan_status_to_completed(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Unread,
        ]);

        // 完了ボタン（POST）を実行
        $response = $this->actingAs($user)->post(route('reading-plans.complete', $plan));

        // データベースのステータスが Completed (3) に書き換わっているか確認
        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::Completed->value,
        ]);

        $response->assertRedirect(route('reading-plans.index'));
    }

    /**
     * 目標期日を超過した読書計画がバッチ処理によって正しく抽出され、
     * リマインダー通知がデータベース（DatabaseChannel）に保存されるかを検証する
     */
    public function test_batch_command_sends_notification_for_expired_plans(): void
    {
        // 1. テストデータの準備
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // 期日を過ぎた読書計画を作成
        $expiredPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->subDay()->format('Y-m-d'),
            'status' => ReadingPlanStatus::Unread,
        ]);

        // 2. コマンドの実行
        $this->artisan('reading-plans:update-status')
            ->assertExitCode(0); // コマンドがエラーなく正常終了（SUCCESS）したか検証

        // 3. アサーション：notificationsテーブルに、指定のデータ構造で通知レコードが生成されているか確認
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
            'read_at' => null, // 初期状態は未読であること
        ]);
    }

    /**
     * 他のユーザーの読書計画を悪意をもって削除（destroy）しようとした際、
     * 認可ポリシーによって403拒否され、データが守られることを検証する
     */
    public function test_destroy_is_forbidden_for_non_owner(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        // 他のユーザーの読書計画
        $otherPlan = ReadingPlan::factory()->create(['user_id' => $otherUser->id, 'book_id' => $book->id]);

        // 自分が他のユーザーの削除URLにDELETEリクエストを送信した時、403 Forbiddenになるか確認
        $response = $this->actingAs($user)->delete(route('reading-plans.destroy', $otherPlan));

        $response->assertStatus(403);
        // データベースからデータが消えていない（守られた）ことを確認
        $this->assertDatabaseHas('reading_plans', ['id' => $otherPlan->id]);
    }

    /**
     * 他のユーザーの読書計画を悪意をもって完了（complete）しようとした際、
     * 認可ポリシーによって403拒否され、ステータスが改ざんされないことを検証する
     */
    public function test_complete_is_forbidden_for_non_owner(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        // 他のユーザーの読書計画（初期状態：Unread）
        $otherPlan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Unread,
        ]);

        // 自分が他のユーザーの完了URLにPOSTリクエストを送信した時、403 Forbiddenになるか確認
        $response = $this->actingAs($user)->post(route('reading-plans.complete', $otherPlan));

        $response->assertStatus(403);
        // ステータスが改ざんされず、Unreadのままであることを検証
        $this->assertDatabaseHas('reading_plans', [
            'id' => $otherPlan->id,
            'status' => ReadingPlanStatus::Unread->value,
        ]);
    }

    /**
     * 本人は自分の読書計画の編集画面（edit）を正常に表示できることを検証する
     */
    public function test_edit_returns_successful_response_for_owner(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $plan = ReadingPlan::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);

        $response = $this->actingAs($user)->get(route('reading-plans.edit', $plan));

        $response->assertStatus(200);

        // 編集画面に、編集対象の読書計画データが正しく渡されているかを検証
        $response->assertViewHas('readingPlan');
    }

    /**
     * 本人は自分の読書計画を正常に更新（update）でき、
     * 同時にステータスが自動的に「読書中（Reading）」へ移行することを検証する
     */
    public function test_update_modifies_own_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // 初期状態は「Unread（未着手）」で作成
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Unread,
        ]);

        $putData = [
            'target_date' => now()->addDays(14)->format('Y-m-d'),
        ];

        $response = $this->actingAs($user)->put(route('reading-plans.update', $plan), $putData);

        // データベースの期日が更新され、かつステータスが「Reading（読書中 = 値は2）」に
        // 自動で書き換わっていることを検証
        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => now()->addDays(14)->format('Y-m-d'),
            'status' => ReadingPlanStatus::Reading->value,
        ]);
        $response->assertRedirect(route('reading-plans.index'));
    }

    /**
     * 本人は自分の読書計画を正常に削除（destroy）できることを検証する
     */
    public function test_destroy_removes_own_reading_plan_successfully(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $plan = ReadingPlan::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);

        $response = $this->actingAs($user)->delete(route('reading-plans.destroy', $plan));

        // データベースから完全に消え去っていることを検証
        $this->assertDatabaseMissing('reading_plans', ['id' => $plan->id]);
        $response->assertRedirect(route('reading-plans.index'));
    }

    /**
     * ログインユーザーが読書計画の新規作成画面（create）を正常に表示できることを検証する
     */
    public function test_create_returns_successful_response_for_owner(): void
    {
        $user = User::factory()->create();

        // 新規作成画面（GET）へアクセスし、コントローラーの未通過行（create）を通過させる
        $response = $this->actingAs($user)->get(route('reading-plans.create'));

        $response->assertStatus(200);
        $response->assertViewHas('books');
    }

    /**
     * 読書計画の新規作成時（store）、攻撃者が不正なステータス（Completed）を
     * リクエストに強制介入させて送信した際、大量代入（Mass Assignment）の脆弱性を
     * すり抜けずに、初期状態（Unread）として安全に保存されるかを検証する
     */
    public function test_vulnerability_mass_assignment_cannot_tamper_initial_status(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // 本来、作成時は「Unread (1)」になるべきですが、ハッカーが裏側を先回りして
        // 「Completed (3) = 最初から読了状態」というパラメータを不正に埋め込んで送信してきたと想定
        $attackData = [
            'book_id' => $book->id,
            'target_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => ReadingPlanStatus::Completed->value,
        ];

        $response = $this->actingAs($user)->post(route('reading-plans.store'), $attackData);

        $response->assertRedirect(route('reading-plans.index'));

        // アプリに脆弱性（Mass Assignment）があれば、ステータスが「Completed (3)」で保存されてしまう
        // しかし、モデルの $fillable によって「status」は完全に無視されるため、攻撃者の改ざんした値（3）は保存されず、
        // 初期状態の「Unread (1)」で保存されていることを検証
        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Unread->value,
        ]);

        // 改ざんされた値（3）のレコードが実在しないことを確認
        $this->assertDatabaseMissing('reading_plans', [
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed->value,
        ]);
    }
}
