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

        // 1. ユーザーの全レビューを取得
        $userReviews = $user->reviews()->with('book.genres')->get();

        // 2. 評価1〜5の分布（件数）を集計
        $ratingDistribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $ratingDistribution[$i] = $userReviews->where('rating', $i)->count();
        }

        // =========================================================================
        // 💡 3. 【修正要件】高評価の書籍（4星以上の書籍を高い順に最大5件）を取得
        // =========================================================================
        $topRatedBooks = $userReviews
            // ① レビューの点数（rating）が「4星以上」のものだけに厳格に絞り込む
            ->filter(function (Review $review) {
                return $review->rating >= 4;
            })
            // ② 評価が高い順（5点 → 4点）にソートする
            ->sortByDesc('rating')
            // ③ レビューオブジェクトから書籍（Book）オブジェクトに変換
            ->map(function (Review $review) {
                $book = $review->book;
                if ($book) {
                    // Blade画面側で点数を表示できるように、動的に平均プロパティに値を詰める
                    $book->reviews_avg_rating = $review->rating;
                }
                return $book;
            })
            // ④ 万が一紐づく書籍が消えていた場合の null を安全に排除
            ->filter()
            // ⑤ 最大5件まで抽出
            ->take(5)
            // ⑥ 配列のキーを綺麗にリセット
            ->values()
            ->toArray();

        // 4. ジャンル別の評価傾向を集計
        // flatMapを使い、各レビューに紐づく複数のジャンルと点数を「平坦な1本の配列」として抽出
        $genreRatingsFormatted = $userReviews->flatMap(function (Review $review): array {
            if (!$review->book || !$review->book->genres) {
                return [];
            }
            
            // ジャンルごとに「ID, 名前, 点数」のペアを持ったコレクションを生成
            return $review->book->genres->map(function ($genre) use ($review): array {
                return [
                    'id'     => $genre->id,
                    'name'   => $genre->name,
                    'rating' => $review->rating,
                ];
            })->all();
        })
        // 抽出した全ジャンルデータを「ジャンルID」ごとにグループ化（groupBy）
        ->groupBy('id')
        // 各ジャンルのグループ（Collection）ごとに件数と平均点を計算（map）
        ->map(function (Collection $group): array {
            $firstItem = $group->first();
            $count = $group->count();
            // 該当ジャンルの全点数の合計を件数で割って平均点を算出
            $avg = $count > 0 ? $group->sum('rating') / $count : 0;

            return [
                'id'             => $firstItem['id'],
                'name'           => $firstItem['name'],
                'count'          => $count,
                'average_rating' => $avg,
            ];
        })
        // 画面のタイトル「ジャンル別評価傾向 TOP5」の通り、平均点が高い順に上位5件を抽出
        ->sortByDesc('average_rating')
        ->take(5)
        ->values();

        // 5. 画面に渡す統計データの配列を作成（既存の変数構造 $stats を100%維持）
        $stats = [
            'summary' => [
                'total_reviews'  => $userReviews->count(),
                'books_read'     => $user->books()->count(),
                'average_rating' => (float) ($userReviews->avg('rating') ?? 0.0),
            ],
            'rating_distribution' => collect($ratingDistribution),
            'top_rated_books'     => $topRatedBooks,
            'genre_ratings'       => $genreRatingsFormatted,
        ];

        return view('reports.index', compact('stats'));
    }
}
