<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\BookStatus;

class ReadingPlan extends Model
{
    use HasFactory;

    /**
     * 複数代入を許可する属性（一括保存の名簿）
     * ★DR09のデータ要件（計画者、対象書籍、期日、状態）を100%安全に通過させます
     */
    protected $fillable = [
        'user_id',
        'book_id',
        'target_date',
        'status',
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

    /**
     * 属性のキャスト定義
     */
    protected function casts(): array
    {
        return [
            // ⭕ 古い BookStatus::class を消して、新しい ReadingPlanStatus::class に変更します！
            'status' => \App\Enums\ReadingPlanStatus::class,
        ];
    }
}
