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
}
