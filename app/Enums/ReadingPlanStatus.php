<?php

namespace App\Enums;

enum ReadingPlanStatus: int
{
    // 🔥 大文字小文字の定義を画面側の要求（Completed）に合わせます
    case Unread = 1;      // 未着手
    case Reading = 2;     // 読書中
    case Completed = 3;   // 完了

    /**
     * 各ステータスの日本語ラベルを返す
     */
    public function label(): string
    {
        return match ($this) {
            self::Unread => '未着手',
            self::Reading => '読書中',
            self::Completed => '完了',
        };
    }

    /**
     * バッジのCSSクラスを返す関数
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Unread => 'bg-gray-100 text-gray-800 font-semibold px-2 py-1 rounded text-xs',
            self::Reading => 'bg-blue-100 text-blue-800 font-semibold px-2 py-1 rounded text-xs',
            self::Completed => 'bg-green-100 text-green-800 font-semibold px-2 py-1 rounded text-xs',
        };
    }
}
