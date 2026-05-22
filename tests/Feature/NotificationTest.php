<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class NotificationTest
 *
 * 通知一覧画面の表示および既読化処理（markAsRead）の正常系・異常系検証を行うテストクラス
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログインユーザーの通知一覧画面が正常に表示されることを検証する
     */
    public function test_index_displays_user_notifications(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $plan = ReadingPlan::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);

        // 通知をデータベースに保存
        $user->notify(new ReadingPlanReminder($plan));

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertStatus(200);

        // 【アサーション】ビューに 'notifications' 変数が渡されていることを検証
        $response->assertViewHas('notifications');
    }

    /**
     * 未読通知が正常に既読化され、read_atにタイムスタンプが記録されることを検証する
     */
    public function test_mark_as_read_successfully_updates_notification(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $plan = ReadingPlan::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);

        // 通知を発行してデータベースに保存
        $user->notify(new ReadingPlanReminder($plan));

        // 生成された通知レコードのIDを取得
        $notification = $user->unreadNotifications()->first();

        // 既読化ボタン（POST）を叩く
        $response = $this->actingAs($user)->post(route('notifications.read', $notification->id));

        // 【アサーション】データベースの read_at が null でなくなっている（既読化成功）ことを検証
        $this->assertNotNull($user->notifications()->find($notification->id)->read_at);
        $response->assertRedirect();
    }

    /**
     * 他人の通知IDを悪意をもって指定した場合、既読化されずにデータが守られることを検証する
     * IDOR（不正直接参照）脆弱性の防御テスト
     */
    public function test_mark_as_read_does_not_update_other_users_notification(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();
        $plan = ReadingPlan::factory()->create(['user_id' => $otherUser->id, 'book_id' => $book->id]);

        // 他のユーザーに通知を発行してデータベースに保存
        $otherUser->notify(new ReadingPlanReminder($plan));
        $otherNotification = $otherUser->unreadNotifications()->first();

        // 【自分】としてログインし、他人の通知IDを指定して既読化を試みる（悪意のあるアクセスをシミュレート）
        $response = $this->actingAs($user)->post(route('notifications.read', $otherNotification->id));

        // 【アサーション】他人の通知は既読化されておらず（nullのまま）、データが完全に隔離されているか検証
        $this->assertNull($otherUser->notifications()->find($otherNotification->id)->read_at);
    }
}
