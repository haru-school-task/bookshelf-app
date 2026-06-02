<?php

namespace App\Console\Commands;

use App\Models\ReadingPlan;
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

        DB::transaction(function () use ($today): void {
            
            $expiredPlans = ReadingPlan::with(['user'])
                ->where('target_date', '<', $today)
                ->whereIn('status', [1, 2])
                ->get();

            $expiredPlans->map(function (ReadingPlan $plan): void {
                $plan->user->notify(new ReminderNotification($plan));
            });
        });

        $this->info('Daily reading plan maintenance task completed successfully.');
        
        return Command::SUCCESS;
    }
}
