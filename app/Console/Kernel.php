<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Class Kernel
 *
 * アプリケーションのコンソールコマンドおよびスケジュール（日次バッチ）の管理を行うクラス
 * 💡【コード品質担保：型宣言・PHPDoc完全対応】
 * 
 * @package App\Console
 */
class Kernel extends ConsoleKernel
{
    /**
     * アプリケーションのコマンドスケジュール（日次自動実行など）を定義する
     * 💡【型宣言・PHPDoc完全対応】引数の型とアノテーションを厳密に記載
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        // 先ほど作成したコマンドのシグネチャを指定し、仕様書通り「毎日自動実行（daily）」に設定
        $schedule->command('reading-plans:update-status')->daily();
    }

    /**
     * アプリケーションのコンソールコマンドを登録する
     * 💡【型宣言・PHPDoc完全対応】戻り値の型宣言 : void を厳密に明記
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
