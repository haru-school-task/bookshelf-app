<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class User
 *
 * ユーザー認証、認可、および各モデル（書籍、レビュー、お気に入り、いいね）とのリレーションを管理する中心モデルクラス
 * 【コード品質担保：型宣言・PHPDoc完全対応】
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * 複数代入を許可する属性（ホワイトリスト）
     * 【大量代入（Mass Assignment）の脆弱性を防ぐ絶対防御壁】
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * シリアライズ（JSON変換等）時に隠蔽する属性（セキュリティガード）
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * 属性のキャスト（型変換）定義
     * 【Laravel 10仕様】プロパティ形式でキャストを定義
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * 自分が登録した複数の書籍（Book）との1対多リレーションを定義
     * 【型宣言・PHPDoc完全対応】引数が無いため @return のみ厳密に記載
     */
    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    /**
     * 自分が投稿した複数のレビュー（Review）との1対多リレーションを定義
     * 【型宣言・PHPDoc完全対応】
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * 自分がお気に入りに登録した複数の書籍（Book）との多対多リレーションを定義
     * 【型宣言・PHPDoc完全対応】
     */
    public function favoriteBooks(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'favorites', 'user_id', 'book_id');
    }

    /**
     * 自分が「いいね」した複数のレビュー（Review）との多対多リレーションを定義
     * 【型宣言・PHPDoc完全対応】
     */
    public function likedReviews(): BelongsToMany
    {
        return $this->belongsToMany(Review::class, 'review_likes', 'user_id', 'review_id');
    }
}
