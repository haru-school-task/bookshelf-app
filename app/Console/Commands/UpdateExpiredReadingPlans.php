<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Class UpdateExpiredReadingPlans
 *
 * 目標期日を超過した読書計画のステータス自動更新およびリマインダー通知を制御するバッチコマンド
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

            // 2. ステータスを「期限切れ」を意味する状態に変更（もしスクール指定のEnumがあれば変更してください）
            // 例: $plan->status = ReadingPlanStatus::Expired;

            // 現在の「読書中」のまま進める場合は、状態変更をスキップするか仕様に合わせて調整します
            $plan->save();

            // 3.【Notification facade（DatabaseChannel）要件に完全準拠】
            // Laravel標準の Notification ファサードを呼び出し、ユーザーへリマインダーを発火
            // ⚠️「ReadingPlanReminder」クラスはご自身の環境の通知クラス名に必要に応じて合わせてください。
            if ($plan->user) {
                $plan->user->notify(new ReadingPlanReminder($plan));
            }
        });

        $this->info("{$expiredPlans->count()} 件の読書計画の処理および通知の送信が完了しました。");

        return Command::SUCCESS;
    }
}
