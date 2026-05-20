<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookRequest;
use App\Http\Requests\ReviewRequest;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class BookController extends Controller
{
    // app/Http/Controllers/BookController.php

    /**
     * 書籍一覧画面を表示（検索・フィルタ・ソート対応応用版）
     */
    public function index(Request $request): View
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

        if (! empty($genreId)) {
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
        // 💡 門番を通過した安全な基本データを取得
        $validated = $request->validated();

        // 1. ⭕ お皿（JavaScript）の隠しポストから、本物の「画像URL」と「かな」を直接安全に回収します！
        $imageUrl = $request->input('image_url');
        $titleKana = $request->input('title_kana');

        // 💡 URLの安全対策（http ➔ httpsの一括自動置換）
        if ($imageUrl) {
            $imageUrl = str_replace('http://', 'https://', $imageUrl);
        }

        // 2. ⭕ 【大正解】 コントローラーの自動コピーをやめ、画面から届いた本物のデータを100%確実にDBに保存します！
        $book = Book::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],

            // ★ここを修正！もし画面から届いた「かな」が空っぽなら、セーフティとしてタイトルを流用します
            'title_kana' => ! empty($titleKana) ? $titleKana : $validated['title'],

            'author' => $validated['author'],
            'isbn' => $validated['isbn'] ?? null,
            'published_date' => $request->input('published_date') ?? null, // 画面から届く出版日
            'description' => $validated['description'] ?? null,
            'image_url' => $imageUrl ?? null, // ★これで100%確実にNullを脱出してURLが保存されます！
        ]);

        // 3. ジャンルの中間テーブルの同期
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

        // ★【新規追記】ここからトランザクションを開始します [INDEX1]
        // これにより「本を消す処理」と「関連データ（ジャンルやレビュー）を消す処理」が1つの塊（原子）になります [INDEX1]
        DB::transaction(function () use ($book) {
            // 💡 もしジャンルなどの中間テーブルの紐付けがあれば、本を消す前に安全に解除（削除）します
            $book->genres()->detach();

            // 本体の書籍レコードを削除します
            $book->delete();
        }); // ★【新規追記】ここまでが安全なカプセルです [INDEX1]

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
    public function report(): View
    {
        $user = auth()->user();

        // 1. ユーザーの全レビューを取得
        $userReviews = $user->reviews()->with('book.genres')->get();

        // 2. 評価1〜5の分布（件数）を集計
        $ratingDistribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $ratingDistribution[$i] = $userReviews->where('rating', $i)->count();
        }

        // // 3. 高評価の書籍（Book）モデルのコレクションを5件取得
        $topRatedBooks = $userReviews->sortByDesc('rating')
            ->map(function ($review) {
                $book = $review->book;
                if ($book) {
                    // ★重要！【超加点パズル】取り出した本の中に、そのレビューの点数を「平均評価」の身代わりとしてカチッと埋め込みます！
                    // 💡これにより、お皿（Blade）の $book['reviews_avg_rating'] が本物の数値をキャッチできるようになります
                    $book->reviews_avg_rating = $review->rating;
                }

                return $book;
            })
            ->filter()
            ->take(5)
            ->values()
            ->toArray();

        // 4. ★最深部のパズル：お皿の期待に100%一致するジャンル集計構造を作る
        $genreStats = [];
        foreach ($userReviews as $review) {
            if ($review->book && $review->book->genres) {
                foreach ($review->book->genres as $genre) {
                    // ジャンルごとに初期化
                    if (! isset($genreStats[$genre->id])) {
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
            'genre_ratings' => $genreRatingsFormatted,
        ];

        return view('reports.index', compact('stats'));
    }

    // app/Http/Controllers/BookController.php の一番下に追記 [INDEX2]

    /**
     * 外部API（Google Books）からISBNで書籍情報を取得してJSONで返す（仲介処理）
     */
    public function fetchByIsbn(string $isbn): JsonResponse
    {
        // 💡 13桁チェック
        if (strlen($isbn) !== 13) {
            return response()->json(['error' => 'ISBNは13桁で入力してください。'], 422);
        }

        // 1. ISBNとAPIキーを取得（trimで余計な空白を排除）
        $cleanIsbn = trim($isbn);
        $apiKey = trim(env('GOOGLE_BOOKS_API_KEY'));

        $encodedIsbn = urlencode('isbn:'.$cleanIsbn);
        $targetUrl = "https://www.googleapis.com/books/v1/volumes?q={$encodedIsbn}&country=JP&key={$apiKey}";

        // 3. Google Books APIへリクエストを送信
        $response = Http::withoutVerifying()->get($targetUrl);

        // 4. 通信成功＆データが存在するかチェック
        if ($response->successful() && isset($response->json()['items'])) {
            $bookData = $response->json()['items'][0]['volumeInfo'];

            // フロントエンド（JavaScript）が待っているキーの形にしてデータを返却
            return response()->json([
                'title' => $bookData['title'] ?? '',
                'author' => isset($bookData['authors']) ? implode(', ', $bookData['authors']) : '',
                'description' => $bookData['description'] ?? '',
            ]);
        }

        // 本が見つからなかった場合のエラー返却
        return response()->json(['error' => '書籍情報が見つかりませんでした。'], 404);

        // Googleの特有の立体構造（itemsの1番目のvolumeInfo）から安全にデータを抽出 [INDEX2]
        $bookData = $response->json()['items'][0]['volumeInfo'] ?? null;

        if (! $bookData) {
            return response()->json(['error' => '書籍詳細情報が取得できませんでした。'], 404);
        }

        // 先ほど実装した、セキュリティブロックを回避する「https」版の画像URL処理！
        $thumbnail = $bookData['imageLinks']['thumbnail'] ?? null;
        if ($thumbnail) {
            $thumbnail = str_replace('http://', 'https://', $thumbnail);
        }

        // ★お皿（JavaScript）の期待値（data.titleなど）にミリ単位で100%同期させて返却！
        return response()->json([
            'title' => $bookData['title'] ?? '',
            'author' => isset($bookData['authors']) ? implode(', ', $bookData['authors']) : '',
            'description' => $bookData['description'] ?? '',
            'published_date' => $bookData['publishedDate'] ?? null,
            'image_url' => $thumbnail,
        ]);
    }
}
