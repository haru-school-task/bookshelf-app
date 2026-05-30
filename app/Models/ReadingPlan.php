<?php

namespace App\Models;

use App\Enums\PlanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 読書計画のデータを管理するモデルクラスです。
 */
class ReadingPlan extends Model
{

    use HasFactory; 
      
    /**
     * 一括代入（保存）を許可する属性名（カラム名）のリストです。
     * 💡【超重要】これらが不足していると、create() を実行した際にデータがすべて無視され、テーブルが空になります。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'book_id',     // 👈 【追加】
        'target_date',  // 👈 【追加】
        'status',
        'completed_at',
    ];

    /**
     * 属性のキャスト定義
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => \App\Enums\ReadingPlanStatus::class, 
        'target_date' => 'date', 
    ];

    /**
     * 計画を所有するユーザーとのリレーション
     *
     * @return BelongsTo<User, ReadingPlan>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 対象の書籍とのリレーション
     *
     * @return BelongsTo<Book, ReadingPlan>
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
