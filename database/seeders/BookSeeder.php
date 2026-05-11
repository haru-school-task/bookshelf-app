<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = \App\Models\User::where('email', 'yamada@example.com')->first();

        $books = [
            ['title' => '吾輩は猫である', 'author' => '夏目漱石', 'isbn' => '9784101010014', 'genres' => ['小説']],
            ['title' => '人を動かす', 'author' => 'D・カーネギー', 'isbn' => '9784422100524', 'genres' => ['ビジネス', '自己啓発']],
            ['title' => 'リーダブルコード', 'author' => 'Dustin Boswell', 'isbn' => '9784873115658', 'genres' => ['技術書']],
            ['title' => '7つの習慣', 'author' => 'スティーブン・R・コヴィー', 'isbn' => '9784863940246', 'genres' => ['ビジネス', '自己啓発']],
            ['title' => '坊っちゃん', 'author' => '夏目漱石', 'isbn' => '9784101010021', 'genres' => ['小説']],
            ['title' => 'サピエンス全史', 'author' => 'ユヴァル・ノア・ハラリ', 'isbn' => '9784309226712', 'genres' => ['歴史', '科学']],
            ['title' => 'Clean Code', 'author' => 'Robert C. Martin', 'isbn' => '9784848330598', 'genres' => ['技術書']],
            ['title' => '嫌われる勇気', 'author' => '岸見一郎・古賀史健', 'isbn' => '9784776205819', 'genres' => ['自己啓発']],
            ['title' => '火花', 'author' => '又吉直樹', 'isbn' => '9784163902302', 'genres' => ['小説']],
            ['title' => 'FACTFULNESS', 'author' => 'ハンス・ロスリング', 'isbn' => '9784822289607', 'genres' => ['ビジネス', '科学']],
            ['title' => 'コンテナ物語', 'author' => 'マルク・レヴィンソン', 'isbn' => '9784822251468', 'genres' => ['ビジネス', '歴史']],
        ];

        foreach ($books as $index => $data) {
            // 書籍の作成（firstOrCreateで重複防止）
            $book = \App\Models\Book::firstOrCreate(
                ['isbn' => $data['isbn']],
                [
                    'user_id' => $admin->id,
                    'title' => $data['title'],
                    'author' => $data['author'],
                    'description' => $data['title'] . 'の解説文がここに入ります。',
                    'image_url' => "https://placehold.jp" . ($index + 1),
                ]
            );

            // ジャンル名からIDを取得して紐付け
            $genreIds = \App\Models\Genre::whereIn('name', $data['genres'])->pluck('id');
            $book->genres()->sync($genreIds);
        }
    }
}
