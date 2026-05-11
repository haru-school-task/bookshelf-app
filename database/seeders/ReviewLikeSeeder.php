<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::all();
        $reviews = \App\Models\Review::all();

        foreach ($reviews as $review) {
            // 自分以外から0〜3人
            $likers = $users->reject(fn($user) => $user->id === $review->user_id)
                ->random(rand(0, 3))
                ->pluck('id');
            $review->likedByUsers()->syncWithoutDetaching($likers);
        }
    }
}
