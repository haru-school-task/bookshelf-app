<?php

namespace App\Enums;

/**
 * 書籍の読書ステータスを定義するEnum
 */
enum BookStatus: int
{
    case UNREAD = 1;   // 積読
    case READING = 2;  // 読書中
    case COMPLETED = 3; // 読了
}
