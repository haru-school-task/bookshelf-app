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
        $users = User::all();

        $books = [
            ['title' => '吾輩は猫である', 'title_kana' => 'わがはいはねこである', 'author' => '夏目漱石', 'isbn' => '9784101010014', 'genres' => ['小説'], 'date' => '1905-01-01'],
            ['title' => '人を動かす', 'title_kana' => 'ひとをうごかす', 'author' => 'D・カーネギー', 'isbn' => '9784422100524', 'genres' => ['ビジネス', '自己啓発'], 'date' => '1936-11-12'],
            ['title' => 'リーダブルコード', 'title_kana' => 'りーだぶるこーど', 'author' => 'Dustin Boswell', 'isbn' => '9784873115658', 'genres' => ['技術書'], 'date' => '2012-06-01', 'image_url' => 'https://books.google.com/books/content?id=Wx1dLwEACAAJ&printsec=frontcover&img=1&zoom=1&source=gbs_api'],
            ['title' => '7つの習慣', 'title_kana' => 'ななつのしゅうかん', 'author' => 'スティーブン・R・コヴィー', 'isbn' => '9784863940246', 'genres' => ['ビジネス', '自己啓発'], 'date' => '1989-10-24'],
            ['title' => '坊っちゃん', 'title_kana' => 'ぼっちゃん', 'author' => '夏目漱石', 'isbn' => '9784101010021', 'genres' => ['小説'], 'date' => '1906-04-01'],
            ['title' => 'サピエンス全史', 'title_kana' => 'さぴえんすぜんし', 'author' => 'ユヴァル・ノア・ハラリ', 'isbn' => '9784309226712', 'genres' => ['歴史', '科学'], 'date' => '2011-01-01', 'image_url' => 'https://books.google.com/books/content?id=z1FPvgAACAAJ&printsec=frontcover&img=1&zoom=1&source=gbs_api'],
            ['title' => 'Clean Code', 'title_kana' => 'くりーんこーど', 'author' => 'Robert C. Martin', 'isbn' => '9784848330598', 'genres' => ['技術書'], 'date' => '2008-08-01'],
            ['title' => '嫌われる勇気', 'title_kana' => 'きらわれるゆうき', 'author' => '岸見一郎・古賀史健', 'isbn' => '9784776205819', 'genres' => ['自己啓発'], 'date' => '2013-12-13'],
            ['title' => '火花', 'title_kana' => 'ひばな', 'author' => '又吉直樹', 'isbn' => '9784163902302', 'genres' => ['小説'], 'date' => '2015-03-11', 'image_url' => 'https://books.google.com/books/content?id=M0cprgEACAAJ&printsec=frontcover&img=1&zoom=1&source=gbs_api'],
            ['title' => 'FACTFULNESS', 'title_kana' => 'ふぁくとふるねす', 'author' => 'ハンス・ロスリング', 'isbn' => '9784822289607', 'genres' => ['ビジネス', '科学'], 'date' => '2018-04-03', 'image_url' => 'https://books.google.com/books/content?id=4GqdwAEACAAJ&printsec=frontcover&img=1&zoom=1&source=gbs_api'],
            ['title' => 'コンテナ物語', 'title_kana' => 'こんてなものがたり', 'author' => 'マルク・レヴィンソン', 'isbn' => '9784822245566', 'genres' => ['ビジネス', '歴史'], 'date' => '2006-01-01'],
        ];

        foreach ($books as $index => $data) {

            $imageUrl = '';

            if (! empty($data['image_url'])) {
                if (preg_match('/(\?id=[^&]+)/', $data['image_url'], $matches)) {
                    $imageUrl = 'https://books.google.com/books/content'.$matches[1];
                } else {
                    $imageUrl = $data['image_url'];
                }
            }

            $book = Book::create([
                'user_id' => $users->random()->id,
                'title' => $data['title'],
                'title_kana' => $data['title_kana'],
                'author' => $data['author'],
                'isbn' => $data['isbn'],
                'published_date' => $data['date'],
                'description' => $data['title'].'の解説文がここに入ります。',
                'image_url' => $imageUrl,
            ]);

            $genreIds = Genre::whereIn('name', $data['genres'])->pluck('id');

            $book->genres()->attach($genreIds);
        }

    }
}
