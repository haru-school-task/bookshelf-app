<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::all();
        $books = \App\Models\Book::all();

        foreach ($users as $user) {
            // 各ユーザーに3〜5冊ランダムに選択
            $favoriteBooks = $books->random(rand(3, 5))->pluck('id');
            $user->favoriteBooks()->syncWithoutDetaching($favoriteBooks);
        }

    }
}