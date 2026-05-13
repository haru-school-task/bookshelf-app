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
            // ★要件：各書籍にジャンル情報を含める
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
            // ★要件：平均評点（レビューがなければ0.0）
            'average_rating' => (float) ($this->reviews_avg_rating ?? 0),
            // ★要件：レビュー件数
            'reviews_count' => (int) ($this->reviews_count ?? 0),
        ];
    }
}
