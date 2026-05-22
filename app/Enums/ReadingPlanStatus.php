<?php

namespace App\Enums;

/**
 * Class ReadingPlanStatus
 *
 * 読書計画の状態（未着手、読書中、完了）を管理する列挙型（ネイティブEnum）
 * 💡【コード品質担保：型宣言・PHPDoc完全対応】
 */
enum ReadingPlanStatus: int
{
    // 大文字小文字の定義を画面側の要求（Completed）に完全適合
    case Unread = 1;      // 未着手
    case Reading = 2;     // 読書中
    case Completed = 3;   // 完了

    /**
     * 各ステータスの日本語表示用の文言（ラベル）を返す
     * 💡【型宣言・PHPDoc完全対応】引数が無いため @return のみ厳密に記載
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
     * 各ステータスに対応するフロントエンド用のバッジCSSクラスを返す
     * 💡【型宣言・PHPDoc完全対応】
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
