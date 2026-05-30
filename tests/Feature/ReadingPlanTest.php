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
 * @package Tests\Feature
 */
class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 読書計画一覧画面が正常に表示され、他のユーザーの計画が表示されないことを検証する
     * 
     * @return void
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
     * バリデーション済みのデータを用いて、読書計画が初期ステータス（1=未着手）で正しく保存されるかを検証する
     * 
     * @return void
     */
    public function test_store_saves_reading_plan_with_valid_data(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // 💡 解決の決定打：after_or_equal:today を確実に通過させるため、
        // 計算を挟まない純粋な「今日の日付（Y-m-d）」を送信して判定ズレを完全に防ぎます。
        $todayDate = \Carbon\Carbon::today()->format('Y-m-d');
        
        $postData = [
            'book_id'     => $book->id,
            'target_date' => $todayDate, // 👈 これで確実にバリデーションを突破します
        ];

        $response = $this->actingAs($user)->post(route('reading-plans.store'), $postData);

        // データベースに本物のデータが安全に書き込まれたか検証
        $this->assertDatabaseHas('reading_plans', [
            'user_id'     => $user->id,
            'book_id'     => $book->id,
            'target_date' => $todayDate,
            'status'      => 1, // 1: 未着手
        ]);

        $response->assertRedirect(route('reading-plans.index'));
    }


    /**
     * 他のユーザーの読書計画の編集画面を開こうとした際、認可ポリシーによって403拒否されるか検証する
     *  
     * @return void
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
     * 
     * @return void
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
     *
     * @return void
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
     *  
     * @return void
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
     * 
     * @return void
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
     * 
     * @return void
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
     * 自身の読書計画の期日を正常に変更（PUT）し、ステータスが「読書中(2)」に書き換わるかを検証する
     * 
     * @return void
     */
    public function test_update_modifies_own_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // 💡 修正ポイント①：初期状態（今日から7日後）でテストデータを安全に作成
        $plan = ReadingPlan::factory()->create([
            'user_id'     => $user->id,
            'book_id'     => $book->id,
            'target_date' => now()->addDays(7)->format('Y-m-d'),
            'status'      => 1,
        ]);

        // 💡 修正ポイント②：更新後の期待値となる「今日から14日後の日付」を完璧に変数化します
        $targetDate = now()->addDays(14)->format('Y-m-d');
        $putData = [
            'target_date' => $targetDate,
        ];

        $response = $this->actingAs($user)->put(route('reading-plans.update', $plan), $putData);

        // 💡 修正ポイント③：データベースが14日後（$targetDate）に正しく書き換わっていることを厳密に検証します
        $this->assertDatabaseHas('reading_plans', [
            'id'          => $plan->id,
            'target_date' => $targetDate, // 👈 実際のDBの変更成功値（2026-06-12）に完璧に一致させます
            'status'      => 2, // 2: 読書中
        ]);
        
        $response->assertRedirect(route('reading-plans.index'));
    }



    /**
     * 本人は自分の読書計画を正常に削除（destroy）できることを検証する
     *  
     * @return void
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
     *
     * @return void 
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
     * 脆弱性テスト：Mass Assignment 脆弱性を利用したステータスの不正改ざんが防御されるかを検証する
     * 
     * @return void
     */
    public function test_vulnerability_mass_assignment_cannot_tamper_initial_status(): void
    {   
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // 💡 解決の決定打：こちらも同様に「今日の日付」を確実に送信し、
        // コントローラー内のバリデーションチェックを無傷で突破させます。
        $todayDate = \Carbon\Carbon::today()->format('Y-m-d');
        
        $attackData = [
            'book_id'     => $book->id,
            'target_date' => $todayDate,
            'status'      => 3, // 攻撃者が一気に「読了(3)」に改ざんしようとした不正データ
        ];

        $response = $this->actingAs($user)->post(route('reading-plans.store'), $attackData);

        // 攻撃者の値（3）はコントローラーの validate() 後の配列から自動消去され、
        // かつモデルの $fillable で守られているため無視され、初期状態の 1 で安全に保存されることを検証
        $this->assertDatabaseHas('reading_plans', [
            'user_id'     => $user->id,
            'book_id'     => $book->id,
            'target_date' => $todayDate,
            'status'      => 1,
        ]);
    }


}
