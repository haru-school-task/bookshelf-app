<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    // いいね機能用
    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'review_likes');
    }

    public function user()
    {
        // 一つのレビューは、一人のユーザーに所属している
        return $this->belongsTo(\App\Models\User::class);
    }

    protected $fillable = ['user_id', 'book_id', 'rating', 'comment'];
}
