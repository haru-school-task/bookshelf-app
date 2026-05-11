<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::all();
        $books = \App\Models\Book::all();
        $comments = ['最高の一冊です！', '非常に勉強になりました。', '何度も読み返したい。', '視点が変わりました。', '万人におすすめしたい。'];

        foreach ($books as $book) {
            // 各書籍に2〜4件のレビューをランダムなユーザーから配分
            $reviewers = $users->random(rand(2, 4));

            foreach ($reviewers as $user) {
                \App\Models\Review::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'rating' => rand(3, 5), // 基本要件：3〜5の範囲
                    'comment' => $comments[array_rand($comments)],
                ]);
            }
        }
    }
}
