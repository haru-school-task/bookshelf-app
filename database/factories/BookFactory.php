<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // ランダムなタイトルを一度変数に代入します
        $title = $this->faker->realText(15);

        return [
            'user_id' => \App\Models\User::factory(), // 本の持ち主（User）も自動で作ってもらう
            'title' => fake()->realText(20),
            'title_kana' => $title,
            'author' => $this->faker->name(),
            'isbn' => $this->faker->isbn13(),
            'description' => fake()->realText(100),
            'image_url' => 'https://placehold.jp', // 適当な画像URL
        ];
    }
}
