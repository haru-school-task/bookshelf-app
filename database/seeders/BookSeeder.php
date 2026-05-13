<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 【要件】すべてのユーザーを取得（ランダム割当用）
        $users = User::all();

        $books = [
            ['title' => '吾輩は猫である', 'author' => '夏目漱石', 'isbn' => '9784101010014', 'genres' => ['小説'], 'date' => '1905-01-01'],
            ['title' => '人を動かす', 'author' => 'D・カーネギー', 'isbn' => '9784422100524', 'genres' => ['ビジネス', '自己啓発'], 'date' => '1936-11-12'],
            ['title' => 'リーダブルコード', 'author' => 'Dustin Boswell', 'isbn' => '9784873115658', 'genres' => ['技術書'], 'date' => '2012-06-01'],
            ['title' => '7つの習慣', 'author' => 'スティーブン・R・コヴィー', 'isbn' => '9784863940246', 'genres' => ['ビジネス', '自己啓発'], 'date' => '1989-10-24'],
            ['title' => '坊っちゃん', 'author' => '夏目漱石', 'isbn' => '9784101010021', 'genres' => ['小説'], 'date' => '1906-04-01'],
            ['title' => 'サピエンス全史', 'author' => 'ユヴァル・ノア・ハラリ', 'isbn' => '9784309226712', 'genres' => ['歴史', '科学'], 'date' => '2011-01-01'],
            ['title' => 'Clean Code', 'author' => 'Robert C. Martin', 'isbn' => '9784848330598', 'genres' => ['技術書'], 'date' => '2008-08-01'],
            ['title' => '嫌われる勇気', 'author' => '岸見一郎・古賀史健', 'isbn' => '9784776205819', 'genres' => ['自己啓発'], 'date' => '2013-12-13'],
            ['title' => '火花', 'author' => '又吉直樹', 'isbn' => '9784163902302', 'genres' => ['小説'], 'date' => '2015-03-11'],
            ['title' => 'FACTFULNESS', 'author' => 'ハンス・ロスリング', 'isbn' => '9784822289607', 'genres' => ['ビジネス', '科学'], 'date' => '2018-04-03'],
            // ★【要件】コンテナ物語のISBNを指示通り「9784822245566」に更新
            ['title' => 'コンテナ物語', 'author' => 'マルク・レヴィンソン', 'isbn' => '9784822245566', 'genres' => ['ビジネス', '歴史'], 'date' => '2006-01-01'],
        ];

        foreach ($books as $index => $data) {
            // ★【要件】firstOrCreate() から create() に変更
            // ★【要件】user_id を User::first() から $users->random()->id によるランダム割当に変更
            $book = Book::create([
                'user_id' => $users->random()->id,
                'title' => $data['title'],
                'author' => $data['author'],
                'isbn' => $data['isbn'],
                'published_date' => $data['date'], // ★【要件】前行程で追加した published_date を流し込む
                'description' => $data['title'] . 'の解説文がここに入ります。',
                // 💡 placehold.jp の正しい画像URLの形式（例: placehold.jp）に整形
                'image_url' => "placehold.jp" . urlencode($data['title']),
            ]);

            // ジャンル名からIDを取得
            $genreIds = Genre::whereIn('name', $data['genres'])->pluck('id');

            // ★【要件】genres()->sync() から genres()->attach() に変更
            $book->genres()->attach($genreIds);
        }
    }
}
