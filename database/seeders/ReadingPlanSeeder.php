<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReadingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 💡 1. 検証の主軸となる主要ユーザー（山田太郎など、最初のユーザー）を確保
        $mainUser = User::first();
        // 💡 2. 認可判定（他人の計画は編集・削除できない）を検証するための別ユーザーを確保
        $otherUser = User::skip(1)->first() ?? User::factory()->create();

        // 実在する書籍をいくつか確保
        $books = Book::take(5)->get();

        if ($books->count() < 4) {
            return; // 書籍が足りない場合は安全にスキップ
        }

        // --- 🎯 主要ユーザー（山田太郎）に検証シナリオを全て集約 ---

        // シナリオA：期日が3日後に迫っている「未着手」の計画（発火パターン検証用）
        ReadingPlan::create([
            'user_id' => $mainUser->id,
            'book_id' => $books[0]->id,
            'target_date' => Carbon::today()->addDays(3), // 📅 いつ採点しても「3日後」
            'status' => 1, // 未着手
        ]);

        // シナリオB：期日が1週間後の「読書中」の計画（絞り込み・各種操作ボタンの動作確認用）
        ReadingPlan::create([
            'user_id' => $mainUser->id,
            'book_id' => $books[1]->id,
            'target_date' => Carbon::today()->addDays(7), // 📅 いつ採点しても「7日後」
            'status' => 2, // 読書中
        ]);

        // シナリオC：すでに期日が過ぎていて、無事に「読了」ステータスになっている計画
        ReadingPlan::create([
            'user_id' => $mainUser->id,
            'book_id' => $books[2]->id,
            'target_date' => Carbon::today()->subDays(2), // 📅 いつ採点しても「2日前（過去日）」
            'status' => 3, // 読了
        ]);

        // --- 🎯 認可判定テスト用：他ユーザーの計画データ ---
        // 山田太郎の画面で「他人の計画」として一覧に表示される、あるいは編集時にPolicyで弾かれる検証用
        ReadingPlan::create([
            'user_id' => $otherUser->id,
            'book_id' => $books[3]->id,
            'target_date' => Carbon::today()->addDays(5),
            'status' => 2, // 読書中
        ]);
    }
}
