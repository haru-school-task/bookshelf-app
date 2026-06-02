<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
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
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $myBook = Book::factory()->create(['title' => '私の愛読書']);
        $otherBook = Book::factory()->create(['title' => '他のユーザーの愛読書']);

        $myPlan = ReadingPlan::factory()->create(['user_id' => $user->id, 'book_id' => $myBook->id]);
        $otherPlan = ReadingPlan::factory()->create(['user_id' => $otherUser->id, 'book_id' => $otherBook->id]);

        $response = $this->actingAs($user)->get(route('reading-plans.index'));

        $response->assertStatus(200);
        $response->assertSee($myPlan->book->title);

        $response->assertDontSee($otherPlan->book->title);
    }

    /**
     * バリデーション済みのデータを用いて、読書計画が初期ステータス（1=未着手）で正しく保存されるかを検証する
     */
    public function test_store_saves_reading_plan_with_valid_data(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $todayDate = Carbon::today()->format('Y-m-d');

        $postData = [
            'book_id' => $book->id,
            'target_date' => $todayDate,
        ];

        $response = $this->actingAs($user)->post(route('reading-plans.store'), $postData);

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $todayDate,
            'status' => 1, // 1: 未着手
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

        $otherPlan = ReadingPlan::factory()->create(['user_id' => $otherUser->id, 'book_id' => $book->id]);

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

        $response = $this->actingAs($user)->post(route('reading-plans.complete', $plan));

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
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $expiredPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->subDay()->format('Y-m-d'),
            'status' => ReadingPlanStatus::Unread,
        ]);

        $this->artisan('reading-plans:update-status')
            ->assertExitCode(0); //

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

        $otherPlan = ReadingPlan::factory()->create(['user_id' => $otherUser->id, 'book_id' => $book->id]);

        $response = $this->actingAs($user)->delete(route('reading-plans.destroy', $otherPlan));

        $response->assertStatus(403);
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

        $otherPlan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Unread,
        ]);

        $response = $this->actingAs($user)->post(route('reading-plans.complete', $otherPlan));

        $response->assertStatus(403);
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

        $response->assertViewHas('readingPlan');
    }

    /**
     * 自身の読書計画の期日を正常に変更（PUT）し、ステータスが「読書中(2)」に書き換わるかを検証する
     */
    public function test_update_modifies_own_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => 1,
        ]);

        $targetDate = now()->addDays(14)->format('Y-m-d');
        $putData = [
            'target_date' => $targetDate,
        ];

        $response = $this->actingAs($user)->put(route('reading-plans.update', $plan), $putData);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $targetDate, //
            'status' => 2, // 2: 読書中
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

        $this->assertDatabaseMissing('reading_plans', ['id' => $plan->id]);
        $response->assertRedirect(route('reading-plans.index'));
    }

    /**
     * ログインユーザーが読書計画の新規作成画面（create）を正常に表示できることを検証する
     */
    public function test_create_returns_successful_response_for_owner(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reading-plans.create'));

        $response->assertStatus(200);
        $response->assertViewHas('books');
    }

    /**
     * 脆弱性テスト：Mass Assignment 脆弱性を利用したステータスの不正改ざんが防御されるかを検証する
     */
    public function test_vulnerability_mass_assignment_cannot_tamper_initial_status(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $todayDate = Carbon::today()->format('Y-m-d');

        $attackData = [
            'book_id' => $book->id,
            'target_date' => $todayDate,
            'status' => 3,
        ];

        $response = $this->actingAs($user)->post(route('reading-plans.store'), $attackData);

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $todayDate,
            'status' => 1,
        ]);
    }
}
