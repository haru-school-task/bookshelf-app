<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        // ★要件：コメントを評価別日本語テンプレート5段階に用意
        $comments = [
            1 => '内容が難しく、途中で挫折してしまいました。',
            2 => '期待していましたが、少し物足りない印象です。',
            3 => '標準的な内容で、初心者向けの解説書として読めます。',
            4 => '非常に実用的で、明日からの開発にすぐ活かせそうです！',
            5 => '文句なしの名著！すべてのアーキテクトに捧げたい一冊。',
        ];

        foreach ($books as $book) {
            // ★要件：各書籍へのレビュー件数をランダム化（各書籍に2〜4件）
            $reviewCount = rand(2, 4);

            $shuffledUsers = $users->shuffle();

            for ($i = 0; $i < $reviewCount; $i++) {
                // ★要件：投稿者をランダム化
                $reviewer = $shuffledUsers[$i];

                if ($reviewer->id === $book->user_id) {
                    continue;
                }

                // ★要件：評価を 1〜5 の全範囲に拡大
                $rating = rand(1, 5);

                Review::create([
                    'book_id' => $book->id,
                    'user_id' => $reviewer->id,
                    'rating' => $rating,
                    'comment' => $comments[$rating],
                ]);
            }
        }
    }
}
