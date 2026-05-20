<?php

namespace App\Enums;

/**
 * 読書計画の状態を管理するEnum（スクール完全同期版）
 */
enum ReadingPlanStatus: int
{
    case UNREAD = 1;    // 未着手
    case READING = 2;   // 読書中
    case COMPLETED = 3;  // 読了

    /**
     * スクール公式のBlade（index.blade.phpの17行目）が呼び出している、
     * 日本語の表示名（ラベル）を返す本物のメソッドです！
     * 
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::UNREAD    => '未着手',
            self::READING   => '読書中',
            self::COMPLETED => '読了',
        };
    }
}
