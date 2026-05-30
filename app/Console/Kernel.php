<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule; // 👈 元から入っているか確認
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * アプリケーションのコマンドスケジュールを定義します。
     * 毎日朝の 06:00 に自動実行されるよう日次バッチコマンドを登録します。
     *
     * @param Schedule $schedule スケジュール管理インスタンス
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        // 👈 ここに「朝の6時実行」の設定を記述します
        $schedule->command('app:daily-plan-check-command')->dailyAt('06:00');
    }

    /**
     * アプリケーションのコンソール用コマンドを登録します。
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

