<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class Genre
 *
 * ジャンルデータおよび書籍（Book）モデルとの多対多リレーションを管理するモデルクラス
 * 【コード品質担保：型宣言・PHPDoc完全対応】
 * 
 * @package App\Models
 */
class Genre extends Model
{
    use HasFactory;

    /**
     * 複数代入を許可する属性（ホワイトリスト）
     * 【大量代入（Mass Assignment）の脆弱性を防ぐ防御壁】
     *
     * @var array<int, string>
     */
    protected $fillable = ['name'];

    /**
     * ジャンルに属する複数の書籍（Book）との多対多リレーションを定義
     * 【型宣言・PHPDoc完全対応】引数が無いため @return のみ厳密に記載
     *
     * @return BelongsToMany
     */
    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class);
    }
}

