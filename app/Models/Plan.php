<?php

namespace App\Models;

use App\Enums\PlanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plan extends Model
{
    /**
     * 属性のキャスト定義
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => PlanStatus::class,
        'due_date' => 'date',
    ];

    /**
     * 計画を所有するユーザーとのリレーション
     *
     * @return BelongsTo<User, Plan>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
