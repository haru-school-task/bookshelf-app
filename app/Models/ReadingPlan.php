<?php

namespace App\Models;

use App\Enums\ReadingPlanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadingPlan extends Model
{
    use HasFactory;

    /**
     * 複数代入を許可する属性
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
     */
    protected $casts = [
        'status' => ReadingPlanStatus::class,
        'target_date' => 'date',
        'completed_at' => 'date',
    ];

    /**
     * リレーション定義：この計画を立てたユーザー
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * リレーション定義：対象の書籍
     */
    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
