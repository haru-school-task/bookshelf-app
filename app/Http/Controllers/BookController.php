<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Http\Requests\BookRequest;
use App\Http\Requests\ReviewRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;


class BookController extends Controller
{
    // app/Http/Controllers/BookController.php

    /**
     * 書籍一覧画面を表示（検索・フィルタ・ソート対応応用版）
     */
    public function index(Request $request): \Illuminate\View\View
    {
        // 1. クエリの土台を作成（N+1問題を避けるため genres を Eagerロード）
        $query = Book::with('genres');

        // 2. 【要件】キーワード検索（タイトルまたは著者名）
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        // 3. 【要件】ジャンルフィルタ（選択されたジャンルIDで絞り込み）
        if ($request->filled('genre_id')) {
            $genreId = $request->input('genre_id');
            $query->whereHas('genres', function ($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }

        // 4. 【要件】並び順ソート（新着順、またはお気に入り数順）
        $sort = $request->input('sort', 'latest'); // 指定がない場合はデフォルトで新着順
        if ($sort === 'popular') {
            // お気に入り（favoriteUsers）の数が多い順に並び替えるプロのクエリ
            $query->withCount('favoriteUsers')->orderBy('favorite_users_count', 'desc');
        } else {
            $query->latest();
        }

        // 5. 【要件】検索条件をページネーションのリンク（2ページ目以降）に完全に引き継ぐ
        $books = $query->paginate(10)->appends($request->all());

        // サイドバーや検索のセレクトボックス用に全ジャンルを取得
        $genres = Genre::all();

        return view('books.index', compact('books', 'genres'));
    }


    /**
     * 書籍を新規登録
     */
    public function store(BookRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $book = Book::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        $book->genres()->sync($validated['genre_ids']);

        return redirect()->route('books.index')->with('success', '書籍を登録しました。');
    }

    /**
     * 書籍詳細画面を表示
     */
    public function show(Book $book): View
    {
        $book->load(['genres', 'reviews.user']);
        return view('books.show', compact('book'));
    }

    /**
     * 書籍編集画面を表示
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book);
        $genres = Genre::all();
        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * 書籍情報を更新
     */
    public function update(BookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);
        $validated = $request->validated();

        $book->update([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        $book->genres()->sync($validated['genre_ids']);

        return redirect()->route('books.index')->with('success', '書籍情報を更新しました。');
    }

    /**
     * 書籍を削除
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);
        $book->delete();

        return redirect()->route('books.index')->with('success', '書籍を削除しました。');
    }

    /**
     * お気に入りの追加・解除（トグル）
     */
    public function toggleFavorite(Book $book): RedirectResponse
    {
        auth()->user()->favoriteBooks()->toggle($book->id);
        return back()->with('success', 'お気に入りを更新しました。');
    }

    /**
     * レビューを投稿
     */
    public function storeReview(ReviewRequest $request, Book $book): RedirectResponse
    {
        if ($book->reviews()->where('user_id', auth()->id())->exists()) {
            return back()->withErrors(['comment' => 'この本には既にレビューを投稿済みです。']);
        }

        $book->reviews()->create([
            'user_id' => auth()->id(),
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return back()->with('success', 'レビューを投稿しました！');
    }

    /**
     * レビューへの「いいね」
     */
    public function toggleLike(Review $review): RedirectResponse
    {
        auth()->user()->likedReviews()->toggle($review->id);
        return back();
    }

    /**
     * レビュー編集画面を表示
     */
    public function editReview(Review $review): View
    {
        $this->authorize('update', $review);
        return view('reviews.edit', compact('review'));
    }

    /**
     * レビューの更新処理
     */
    public function updateReview(ReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);
        $review->update($request->validated());

        return redirect()->route('books.show', $review->book_id)->with('success', 'レビューを更新しました。');
    }

    /**
     * レビューの削除処理
     */
    public function destroyReview(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);
        $review->delete();

        return back()->with('success', 'レビューを削除しました。');
    }

    /**
     * お気に入り書籍一覧を表示
     */
    public function favorites(): View
    {
        $books = auth()->user()->favoriteBooks()->paginate(10);
        $genres = Genre::all();
        return view('favorites.index', compact('books', 'genres'));
    }

    /**
     * ランキング画面を表示
     */
    public function ranking(): View
    {
        $rankedBooks = Book::withCount('favoriteUsers')
            ->orderBy('favorite_users_count', 'desc')
            ->limit(10)
            ->get();

        return view('ranking.index', compact('rankedBooks'));
    }

    // app/Http/Controllers/BookController.php

    /**
     * マイ読書レポート画面を表示（応用版PG14・ジャンル構造完全一致版）
     */
    public function report(): \Illuminate\View\View
    {
        $user = auth()->user();

        // 1. ユーザーの全レビューを取得
        $userReviews = $user->reviews()->with('book.genres')->get();

        // 2. 評価1〜5の分布（件数）を集計
        $ratingDistribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $ratingDistribution[$i] = $userReviews->where('rating', $i)->count();
        }

        // 3. 高評価の書籍（Book）モデルのコレクションを5件取得
        $topRatedBooks = $userReviews->sortByDesc('rating')
            ->map(function ($review) {
                return $review->book;
            })
            ->filter()
            ->take(5)
            ->values();

        // 4. ★最深部のパズル：お皿の期待に100%一致するジャンル集計構造を作る
        $genreStats = [];
        foreach ($userReviews as $review) {
            if ($review->book && $review->book->genres) {
                foreach ($review->book->genres as $genre) {
                    // ジャンルごとに初期化
                    if (!isset($genreStats[$genre->id])) {
                        $genreStats[$genre->id] = [
                            'id' => $genre->id,
                            'name' => $genre->name,
                            'ratings' => [],
                        ];
                    }
                    // レビューの点数をためていく
                    $genreStats[$genre->id]['ratings'][] = $review->rating;
                }
            }
        }

        // お皿がループで綺麗に処理できるように [0 => ['id'=>..., 'name'=>..., 'count'=>..., 'avg_rating'=>...]] の形に整形
        $genreRatingsFormatted = collect($genreStats)->map(function ($item) {
            $count = count($item['ratings']);
            $avg = $count > 0 ? array_sum($item['ratings']) / $count : 0;
            return [
                'id' => $item['id'],
                'name' => $item['name'],
                'count' => $count,
                'avg_rating' => $avg,
            ];
        })
            // 画面のタイトル「ジャンル別評価傾向 TOP5」の通り、平均点が高い順に上位5件を抽出
            ->sortByDesc('avg_rating')
            ->take(5)
            ->values(); // インデックスを 0, 1, 2... に綺麗にリセット！

        // 5. すべてをお皿に盛り付けて返却
        $stats = [
            'summary' => [
                'total_reviews' => $userReviews->count(),
                'books_read' => $user->books()->count(),
                'average_rating' => (float) ($userReviews->avg('rating') ?? 0.0),
            ],
            'rating_distribution' => collect($ratingDistribution),
            'top_rated_books' => $topRatedBooks,
            // ★お皿が待っていた完璧なジャンルTOP5のデータを流し込みます
            'genre_ratings' => $genreRatingsFormatted
        ];

        return view('reports.index', compact('stats'));
    }

}