<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'description' => $this->description,
            'image_url'   => $this->image_url, 
            // ★要件：各書籍にジャンル情報を含める
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
            // ★要件：平均評点（レビューがなければ0.0）
            'average_rating' => (float) ($this->reviews_avg_rating ?? 0),
            // ★要件：レビュー件数
            'reviews_count' => (int) ($this->reviews_count ?? 0),

            // ★【新規追記！】AP02（詳細API）専用のレビュー情報パズル [INDEX1]
            // whenLoaded を使うことで、一覧APIのときは余計なデータを非表示にし、
            // 詳細API（show）で $book->load('reviews.user') されたときだけ、この中身が自動で展開されます！ [INDEX2]
            'reviews' => $this->whenLoaded('reviews', function () {
                return $this->reviews->map(function ($review) {
                    return [
                        'user_name' => $review->user ? $review->user->name : '名無しユーザー', // 投稿者名
                        'rating' => (int) $review->rating,                                // 評価
                        'comment' => $review->comment,                                     // コメント
                        'created_at' => $review->created_at ? $review->created_at->toIso8601String() : null, // 投稿日時
                    ];
                });
            }),
        ];
    }
}
