<?php

namespace App\Models;

use App\Enums\ReadingPlanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ReadingPlan
 *
 * 読書計画データおよび各モデル（ユーザー、書籍）とのリレーションを管理するモデルクラス
 * 💡【コード品質担保：型宣言・PHPDoc完全対応】
 */
class ReadingPlan extends Model
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
        'book_id',
        'target_date',
        'status',
    ];

    /**
     * 💡【Laravel 10仕様】プロパティ形式でキャストを定義
     * これにより、Laravel 10環境で確実に日付オブジェクトへ変換されます
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => ReadingPlanStatus::class,
        'target_date' => 'date',
        'completed_at' => 'date',
    ];

    /**
     * リレーション定義：この計画を立てたユーザー（User）とのリレーション
     * 💡【型宣言・PHPDoc完全対応】戻り値の型を厳密に宣言
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * リレーション定義：対象の書籍（Book）とのリレーション
     * 💡【型宣言・PHPDoc完全対応】戻り値の型を厳密に宣言
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
