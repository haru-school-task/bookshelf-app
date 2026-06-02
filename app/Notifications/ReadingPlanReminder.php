<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Class ReadingPlanReminder
 *
 * 読書計画の目標期日を超過したユーザーに対してリマインダーを通知するクラス
 * 【コード品質担保：型宣言・PHPDoc完全対応】
 * 
 * @package App\Notifications
 */
class ReadingPlanReminder extends Notification
{
    use Queueable;

    /**
     * 対象となる読書計画モデルのインスタンス
     *
     * @var ReadingPlan
     */
    protected ReadingPlan $readingPlan;

    /**
     * Create a new notification instance.
     *
     * 【型宣言・PHPDoc完全対応】引数のアノテーションを厳密に明記
     *
     * @param \App\Models\ReadingPlan $readingPlan
     */
    public function __construct(ReadingPlan $readingPlan)
    {
        $this->readingPlan = $readingPlan;
    }

    /**
     * 通知を送信するチャンネルを決定する
     *
     * 【型宣言・PHPDoc完全対応】
     * 【Notification facade (DatabaseChannel) 要件】データベース保存を指定
     *
     * @param mixed $notifiable
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    /**
     * データベースに保存する配列データを定義します。
     * 【型宣言・PHPDoc完全対応】
     *
     * @param mixed $notifiable
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        $formattedDate = \Carbon\Carbon::parse($this->readingPlan->target_date)->format('Y-m-d');
        $bookTitle = $this->readingPlan->book->title ?? '書籍';

        return [
            'reading_plan_id' => $this->readingPlan->id,
            'book_title'      => $bookTitle,
            'target_date'     => $formattedDate,
            'timing'          => 'three_days_before',
            'message'         => '「' . $bookTitle . '」の目標期日（' . $formattedDate . '）が近づいています。',
        ];
    }

}
