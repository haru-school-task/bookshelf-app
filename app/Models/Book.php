<?php

namespace App\Models;

use App\Enums\BookStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'title', 'title_kana', 'author', 'isbn', 'published_date', 'description', 'image_url'];

    // 書籍の持ち主（User）との絆
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 書籍に紐づく複数のジャンル（Genre）との絆
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    // 書籍に寄せられた複数のレビュー（Review）との絆
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // お気に入りしてくれた複数のユーザー（User）との絆（ランキングで使用）
    public function favoriteUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites', 'book_id', 'user_id');
    }

    /**
     * 属性のキャスト定義
     */
    protected function casts(): array
    {
        return [
            // 💡 もしデータベースに「status」というカラムがあった場合、
            // 単なる数字（1, 2）ではなく、自動的に上で作った BookStatus 型のオブジェクトに一発変換します！
            'status' => BookStatus::class,
        ];
    }
}
