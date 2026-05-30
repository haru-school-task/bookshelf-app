<?php

namespace App\Enums;

/**
 * 読書計画の進捗状態（ステータス）を管理する整数Backed Enum型です。
 * スクール既存のデータベース設計（1=進行中, 2=期限切れ, 3=完了）に完全準拠します。
 */
enum PlanStatus: int
{
    /**
     * 進行中の計画状態
     */
    case ACTIVE = 1;

    /**
     * 期日を超過した期限切れの状態
     */
    case EXPIRED = 2;

    /**
     * 完了済みの計画状態
     */
    case COMPLETED = 3;
}
