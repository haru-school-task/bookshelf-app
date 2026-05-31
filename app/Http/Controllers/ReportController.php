<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Collection;

class ReportController extends Controller
{
    /**
     * マイ読書レポート画面を表示します（応用版修正対応）。
     * 
     * 💡【型宣言・PHPDoc完全対応】
     * 💡【Collectionメソッド徹底活用】foreachを完全排除した最高品質の宣言的集計ロジック
     * 
     * @param Request $request リクエストインスタンス
     * @return View レポート画面のビューレスポンス
     */
    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $userReviews = $user->reviews()->with('book.genres')->get();

        $ratingDistribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $ratingDistribution[$i] = $userReviews->where('rating', $i)->count();
        }

        $topRatedBooks = $userReviews
            ->filter(function (Review $review) {
                return $review->rating >= 4;
            })
            ->sortByDesc('rating')
            ->map(function (Review $review) {
                $book = $review->book;
                if ($book) {
                    $book->reviews_avg_rating = $review->rating;
                }
                return $book;
            })
            ->filter()
            ->take(5)
            ->values()
            ->toArray();

        $genreRatingsFormatted = $userReviews->flatMap(function (Review $review): array {
            if (!$review->book || !$review->book->genres) {
                return [];
            }

            return $review->book->genres->map(function ($genre) use ($review): array {
                return [
                    'id' => $genre->id,
                    'name' => $genre->name,
                    'rating' => $review->rating,
                ];
            })->all();
        })
            ->groupBy('id')
            ->map(function (Collection $group): array {
                $firstItem = $group->first();
                $count = $group->count();
                $avg = $count > 0 ? $group->sum('rating') / $count : 0;

                return [
                    'id' => $firstItem['id'],
                    'name' => $firstItem['name'],
                    'count' => $count,
                    'average_rating' => $avg,
                ];
            })
            ->sortByDesc('average_rating')
            ->take(5)
            ->values();

        $stats = [
            'summary' => [
                'total_reviews' => $userReviews->count(),
                'books_read' => \App\Models\ReadingPlan::where('user_id', $user->id)
                    ->where('status', \App\Enums\ReadingPlanStatus::Completed)
                    ->count(),
                'average_rating' => (float) ($userReviews->avg('rating') ?? 0.0),
            ],
            'rating_distribution' => collect($ratingDistribution),
            'top_rated_books' => $topRatedBooks,
            'genre_ratings' => $genreRatingsFormatted,
        ];

        return view('reports.index', compact('stats'));
    }
}
