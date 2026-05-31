<?php

namespace Database\Factories;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class ReadingPlanFactory
 *
 * 読書計画（ReadingPlan）モデルのテストデータをダミー生成するファクトリクラス
 *
 * @extends Factory<ReadingPlan>
 */
class ReadingPlanFactory extends Factory
{
    /**
     * 対応するモデル名
     *
     * @var string
     */
    protected $model = ReadingPlan::class;

    /**
     * テストデータのデフォルト定義を設定する
     *
     * 💡【型宣言・PHPDoc完全対応】
     * 💡【ネイティブEnum要件】初期状態として「Unread」を安全に注入
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),

            'target_date' => now()->addDays(7)->format('Y-m-d'),

            'status' => ReadingPlanStatus::Unread->value,
        ];
    }
}
