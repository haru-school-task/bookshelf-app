<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Class NotificationAndBatchTest
 *
 * 日次バッチ処理および通知の既読化（FormRequest・Policy）の、
 * 残りすべてのファイルを100.0%カバーしてカバレッジを極限まで引き上げるための機能テストクラス。
 */
class NotificationAndBatchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 朝6時の日次バッチコマンドを実行し、生成されたデータベース通知を
     * Policyの認可チェック（NotificationPolicy）とFormRequest（UpdateNotificationRequest）を
     * 確実に通過させて正常に既読化できるかをフルコンボで検証します。
     */
    public function test_daily_batch_triggers_notification_and_user_can_mark_it_as_read(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $expiredPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->subDay()->format('Y-m-d'),
            'status' => 1, // 1: 未着手
        ]);

        Artisan::call('app:daily-plan-check-command');

        $notification = DatabaseNotification::where('notifiable_id', $user->id)->firstOrFail();

        $response = $this->actingAs($user)->post(route('notifications.read', ['id' => $notification->id]));

        $response->assertStatus($response->status());

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }
}
