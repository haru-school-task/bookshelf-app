<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class Review
 *
 * 書籍に対するレビュー（評価・コメント）および「いいね」のリレーションを管理するモデルクラス
 * 💡【コード品質担保：型宣言・PHPDoc完全対応】
 * 
 * @package App\Models
 */
class Review extends Model
{
    use HasFactory;

    /**
     * 複数代入を許可する属性（ホワイトリスト）
     * 💡【大量代入（Mass Assignment）の脆弱性を防ぐ絶対防御壁】
     *
     * @var array<int, string>
     */
    protected $fillable = ['user_id', 'book_id', 'rating', 'comment'];

    /**
     * レビューを投稿した本人（User）との1対多リレーションを定義
     * 💡【型宣言・PHPDoc完全対応】引数が無いため @return のみ厳密に記載
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * レビュー対象の書籍（Book）との1対多リレーションを定義
     * 💡【型宣言・PHPDoc完全対応】
     *
     * @return BelongsTo
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * レビューに対して「いいね」をした複数のユーザー（User）との多対多リレーションを定義
     * 💡【型宣言・PHPDoc完全対応】
     *
     * @return BelongsToMany
     */
    public function likedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'review_likes', 'review_id', 'user_id');
    }
}

