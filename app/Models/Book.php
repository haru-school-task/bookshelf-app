<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;


    public function genres()
    {
        // 多対多の関係（book_genreテーブルを介してGenreと繋がる）
        return $this->belongsToMany(Genre::class);
    }

    // ↓ これを追記！
    public function reviews()
    {
        // 一冊の本は、たくさんのレビューを持っている（1対多）
        return $this->hasMany(Review::class);
    }
}