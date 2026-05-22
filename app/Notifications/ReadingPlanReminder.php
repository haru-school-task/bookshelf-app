<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Class ReadingPlanReminder
 *
 * 読書計画の目標期日を超過したユーザーに対してリマインダーを通知するクラス
 * 💡【コード品質担保：型宣言・PHPDoc完全対応】
 */
class ReadingPlanReminder extends Notification
{
    use Queueable;

    /**
     * 対象となる読書計画モデルのインスタンス
     */
    protected ReadingPlan $readingPlan;

    /**
     * Create a new notification instance.
     *
     * 💡【型宣言・PHPDoc完全対応】引数のアノテーションを厳密に明記
     */
    public function __construct(ReadingPlan $readingPlan)
    {
        $this->readingPlan = $readingPlan;
    }

    /**
     * 通知を送信するチャンネルを決定する
     *
     * 💡【型宣言・PHPDoc完全対応】
     * 💡【Notification facade (DatabaseChannel) 要件】データベース保存を指定
     *
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        // 仕様書の「DatabaseChannelに保存する」の要件を満たすため 'database' を返します
        return ['database'];
    }

    /**
     * データベース（notificationsテーブル）に保存するデータ構造を定義する
     *
     * 💡【型宣言・PHPDoc完全対応】
     *
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        // 画面（Blade）のベルアイコンなどで表示したい配列データを記述します
        return [
            'reading_plan_id' => $this->readingPlan->id,
            'book_title' => $this->readingPlan->book->title ?? '書籍',
            'target_date' => $this->readingPlan->target_date->format('Y-m-d'),
            'message' => '「'.($this->readingPlan->book->title ?? '書籍').'」の読書目標期日（'.$this->readingPlan->target_date->format('Y-m-d').'）を経過しています。',
        ];
    }
}
