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
            'image_url' => $this->image_url,
            
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
            
            'average_rating' => (float) ($this->reviews_avg_rating ?? 0),
            
            'reviews_count' => (int) ($this->reviews_count ?? 0),

            
            'reviews' => $this->whenLoaded('reviews', function () {
                return $this->reviews->map(function ($review) {
                    return [
                        'user_name' => $review->user ? $review->user->name : '名無しユーザー', 
                        'rating' => (int) $review->rating,                                
                        'comment' => $review->comment,                                     
                        'created_at' => $review->created_at ? $review->created_at->toIso8601String() : null,
                    ];
                });
            }),
        ];
    }
}
