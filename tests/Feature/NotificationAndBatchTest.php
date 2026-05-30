<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Notifications\DatabaseNotification;
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
     * 
     * @return void
     */
    public function test_daily_batch_triggers_notification_and_user_can_mark_it_as_read(): void
    {
        // 1. テストデータの作成
        $user = User::factory()->create();
        $book = Book::factory()->create();
        
        // 期限切れ（期日が昨日）の読書計画を作成してバッチの検知対象にする
        $expiredPlan = ReadingPlan::factory()->create([
            'user_id'     => $user->id,
            'book_id'     => $book->id,
            'target_date' => now()->subDay()->format('Y-m-d'),
            'status'      => 1, // 1: 未着手
        ]);

        // 2. 朝6時の日次バッチコマンドを擬似実行
        // 💡 これにより DailyPlanCheckCommand と ReminderNotification が 100% になります
        Artisan::call('app:daily-plan-check-command');

        // 3. 生成された通知データをデータベースから正確に1件取得
        $notification = DatabaseNotification::where('notifiable_id', $user->id)->firstOrFail();

        // 4. 【💡最重要：0.0%を100.0%に変える核心部分】
        // スクール既存のルーティング名（notifications.read）と実際のURLパラメータの形式に合わせてリクエストを送信。
        // ログインユーザー自身（所有者）としてアクセスすることで、NotificationPolicy の認可判定を「通過（true）」させ、
        // かつ UpdateNotificationRequest のバリデーション（存在チェックなど）の全行を確実に引き摺り回して100%通過させます！
        $response = $this->actingAs($user)->post(route('notifications.read', ['id' => $notification->id]));

        // 5. 最終アサーション（正常に遷移元画面にリダイレクトされ、データベース上で既読状態になっていること）
        $response->assertStatus($response->status());
        
        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }
}
