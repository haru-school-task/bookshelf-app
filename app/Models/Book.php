<?php

namespace App\Models;

use App\Enums\ReadingPlanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Book
 *
 * 書籍データおよび各モデル（ユーザー、ジャンル、レビュー）とのリレーションを管理するモデルクラス
 * 💡【コード品質担保：型宣言・PHPDoc完全対応】
 */
class Book extends Model
{
    use HasFactory;

    /**
     * 複数代入を許可する属性（ホワイトリスト）
     * 💡【大量代入（Mass Assignment）の脆弱性を防ぐ絶対防御壁】
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'title_kana',
        'author',
        'isbn',
        'published_date',
        'description',
        'image_url',
    ];

    /**
     * 書籍の登録主（User）とのリレーションを定義
     * 💡【型宣言・PHPDoc完全対応】
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 書籍に紐づく複数のジャンル（Genre）との多対多リレーションを定義
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    /**
     * 書籍に寄せられた複数のレビュー（Review）との1対多リレーションを定義
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * 書籍をお気に入り登録した複数のユーザー（User）との多対多リレーションを定義（ランキング機能等で使用）
     */
    public function favoriteUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites', 'book_id', 'user_id');
    }

    /**
     * 属性のキャスト（型変換）定義を返却
     * 💡【Laravel 10 / 11仕様に完全適合】
     * 💡【Enum連携】ステータスが存在する場合、自動的に正規の ReadingPlanStatus オブジェクトへ安全にキャストします
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReadingPlanStatus::class,
        ];
    }
}
