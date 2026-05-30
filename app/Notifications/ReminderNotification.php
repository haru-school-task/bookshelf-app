<?php

namespace App\Notifications;

use App\Models\ReadingPlan; // 👈 ここを Plan から ReadingPlan に修正
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReminderNotification extends Notification
{
    use Queueable;

    /**
     * 新しい通知インスタンスを生成します。
     *
     * @param ReadingPlan $plan 読書計画モデルインスタンス
     */
    public function __construct(
        protected ReadingPlan $plan // 👈 受け取る型を ReadingPlan に修正
    ) {}

    /**
     * 通知を送信するチャンネルを定義します（データベース保存を選択）。
     *
     * @param mixed $notifiable 通知対象となるユーザー等のインスタンス
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

        /**
     * データベースの notifications テーブルへ保存する配列データを定義します。
     * 渡された日付データが文字列の場合でも、Carbon::parse を用いて安全にフォーマットします。
     *
     * @param mixed $notifiable 通知対象となるユーザー等のインスタンス
     * @return array<string, mixed> 保存される通知データの配列
     */
    public function toArray(mixed $notifiable): array
    {
        // 💡 修正＆安全対策：
        // 変数名を $this->plan に合わせつつ、Carbon::parse() を使って文字列のままでも確実に動かします
        $formattedDate = \Carbon\Carbon::parse($this->plan->target_date)->format('Y-m-d');
        $bookTitle = $this->plan->book->title ?? '書籍';

        return [
            'plan_id'     => $this->plan->id,
            'book_title'  => $bookTitle,
            'target_date' => $formattedDate,
            
            // 💡 スクール指定のデザインロジック（青・黄・赤）に合わせて期限切れ検知用の 'three_days_after' をセット
            'timing'      => 'three_days_after',
            
            // 💡 メッセージも日本語化して、画面で見やすくなるように組み立てます
            'message'     => '「' . $bookTitle . '」の読書目標期日（' . $formattedDate . '）を経過しています。',
        ];
    }

}
