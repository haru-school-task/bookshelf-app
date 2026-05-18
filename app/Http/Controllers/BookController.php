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

        
        // ★修正：画面から「genre_id」または「genre」という名前で届いたデータを、両方チェックして受け止めます！
        $genreId = $request->input('genre_id') ?? $request->input('genre');

        if (!empty($genreId)) {
            // 絆（多対多）を辿り、中間テーブルの中にこのIDがある本だけに美しく絞り込みます
            $query->whereHas('genres', function ($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }
        // ★修正点1：画面のプルダウンの value 属性が何であっても確実にキャッチします
        $sort = $request->input('sort', 'latest'); 

        switch ($sort) {
            // ★修正点2：もし画面から 'title' だけでなく 'title_asc' という名前で届いても、同じ昇順部屋（asc）へ案内します
            case 'title':
            case 'title_asc':
                $query->orderBy('title_kana', 'asc');
                break;
        
            case 'oldest':
                $query->oldest();
                break;
        
            case 'rating':
                $query->withAvg('reviews', 'rating')
                      ->orderByRaw('reviews_avg_rating IS NULL ASC')
                      ->orderBy('reviews_avg_rating', 'desc');
                break;
        
            case 'latest':
            default:
                $query->latest();
                break;
        }

        // 5. 【要件】検索条件をページネーションのリンク（2ページ目以降）に完全に引き継ぐ
        $books = $query->paginate(10)->appends($request->all());

        // サイドバーや検索のセレクトボックス用に全ジャンルを取得
        $genres = Genre::all();

        return view('books.index', compact('books', 'genres'));
    }

    /**
     * ★【新規追記！】書籍登録画面を表示（応用版PG03）
     * 
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        // 登録画面のセレクトボックス（またはチェックボックス）用に全ジャンルを取得
        $genres = Genre::all();

        // 登録画面のお皿（books.create）に盛り付けて出す
        return view('books.create', compact('genres'));
    }

    /**
     * 書籍を新規登録
     */
    public function store(BookRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $imageUrl = $request->input('image_url');
        if ($imageUrl) {
            // 💡 プロのセキュリティ対策：届いたURLの中に「http://」があれば、強制的に「https://」へ一括書き換えします [INDEX1.3.1]
            $imageUrl = str_replace('http://', 'https://', $imageUrl);
        }

        $book = Book::create([
            'user_id'     => auth()->id(),
            'title'       => $validated['title'],
            'author'      => $validated['author'],
            'isbn'        => $validated['isbn'] ?? null,
            'description' => $validated['description'] ?? null,
            'image_url'   => $imageUrl ?? null, // 🔥 安全になった本物のURLをDBへ保存！,
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
        // ★仕様要件：レビュー平均評価のTOP10（レビューなしは表示しない）
        $rankedBooks = Book::has('reviews') // ★要件：レビューがある書籍だけに絞る [INDEX1]
            ->withAvg('reviews', 'rating') // 平均評点を計算 [INDEX2]
            ->orderBy('reviews_avg_rating', 'desc') // 評価が高い順
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
                'average_rating' => $avg,
            ];
        })
            // 画面のタイトル「ジャンル別評価傾向 TOP5」の通り、平均点が高い順に上位5件を抽出
            ->sortByDesc('average_rating')
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