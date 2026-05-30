<?php

namespace App\Console\Commands;

use App\Models\ReadingPlan;
use App\Enums\ReadingPlanStatus;
use App\Notifications\ReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DailyPlanCheckCommand extends Command
{
    /**
     * コマンドのシグネチャ（実行名）
     *
     * @var string
     */
    protected $signature = 'app:daily-plan-check-command';

    /**
     * コマンドの概要説明
     *
     * @var string
     */
    protected $description = 'Automatically detect expired reading plans and trigger reminder notifications.';

    /**
     * 朝6時の日次バッチ処理を実行します。
     * 手続き的なループを排除し、Collectionメソッドとデータベーストランザクションを用いて原子性を担保します。
     *
     * @return int
     */
    public function handle(): int
    {
        $today = Carbon::today();

        // 複数のSQL処理を一連の不可分な単位として実行するため、トランザクションを開始
        DB::transaction(function () use ($today): void {
            
            // 💡 安全な数値判定への切り替え：
            // スクール既存設計の「未着手(1)」または「読書中(2)」のままで、期日が過去日になっている計画を抽出
            // N+1問題を防ぐため、通知を送信するリレーション（user）を Eager Loading で一括取得
            $expiredPlans = ReadingPlan::with(['user'])
                ->where('target_date', '<', $today)
                ->whereIn('status', [1, 2]) // 👈 ここを [1, 2] に変更して定義エラーを確実に回避します
                ->get();

            // foreach等の手続き的ループを徹底して排除し、宣言的なCollectionメソッド（map）で通知を発火
            $expiredPlans->map(function (ReadingPlan $plan): void {
                $plan->user->notify(new ReminderNotification($plan));
            });
        });

        $this->info('Daily reading plan maintenance task completed successfully.');
        
        return Command::SUCCESS;
    }
}
