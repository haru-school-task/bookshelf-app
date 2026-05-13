<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Review extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'book_id', 'rating', 'comment'];

    // レビューを書いた本人（User）との絆
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // レビュー対象の書籍（Book）との絆
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    // ★メソッド名を likedByUsers に変更！
    public function likedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class, 'review_likes', 'review_id', 'user_id');
    }
}
