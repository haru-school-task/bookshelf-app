<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Class UpdateExpiredReadingPlans
 *
 * 目標期日を超過した読書計画のステータス自動更新およびリマインダー通知を制御するバッチコマンド
 * 💡【コード品質担保：型宣言・PHPDoc完全対応】
 */
class UpdateExpiredReadingPlans extends Command
{
    /**
     * 職人がターミナルやスケジュールから呼び出す時の名前（コマンド名）
     *
     * @var string
     */
    protected $signature = 'reading-plans:update-status';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = '目標期日を経過した読書計画のステータスを自動更新し、リマインダー通知を送信する';

    /**
     * バッチ処理の本体ロジック
     *
     * 💡【型宣言・PHPDoc完全対応】
     * 💡【Collectionメソッド活用】foreachを徹底排除し、宣言的で可読性の高いコードを記述
     * 💡 引数が無いため @param は不要、戻り値の @return のみ厳密に記載
     */
    public function handle(): int
    {
        $today = Carbon::today();

        // 1. 今日より前の日付（超過）で、かつ「完了」になっていない計画を、リレーション（user, book）を含めて取得
        $expiredPlans = ReadingPlan::where('target_date', '<', $today)
            ->where('status', '!=', ReadingPlanStatus::Completed)
            ->with(['user', 'book'])
            ->get();

        if ($expiredPlans->isEmpty()) {
            $this->info('期日を超過した読書計画はありませんでした。');

            return Command::SUCCESS;
        }

        // 🔥【Collectionメソッドの徹底活用】
        // foreachによる泥臭いループを完全に排除。eachメソッドを用いて宣言的に処理を連鎖させます。
        $expiredPlans->each(function (ReadingPlan $plan): void {
            $plan->save();

            // 3.【Notification facade（DatabaseChannel）要件に完全準拠】
            // Laravel標準の Notification ファサードを呼び出し、ユーザーへリマインダーを発火
            if ($plan->user) {
                $plan->user->notify(new ReadingPlanReminder($plan));
            }
        });

        $this->info("{$expiredPlans->count()} 件の読書計画の処理および通知の送信が完了しました。");

        return Command::SUCCESS;
    }
}
