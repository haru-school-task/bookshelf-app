<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        foreach ($users as $user) {
            // 各ユーザーに3〜5冊ランダムに選択
            $favoriteBooks = $books->random(rand(3, 5))->pluck('id');
            $user->favoriteBooks()->syncWithoutDetaching($favoriteBooks);
        }

    }
}
