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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

/**
 * Class BookController
 *
 * 書籍管理機能（一覧、検索、登録、編集、更新、削除）の制御を行うコントローラー
 * 【コード品質担保：型宣言・PHPDoc】
 */
class BookController extends Controller
{
    /**
     * 書籍一覧画面を表示（検索・フィルタ・ソート対応)
     */
    public function index(Request $request): View
    {

        $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
        ], [
            'keyword.max' => '検索キーワードは100文字以内で入力してください。',
        ]);

        $query = Book::with('genres');

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        $genreId = $request->input('genre_id') ?? $request->input('genre');

        if (! empty($genreId)) {
            $query->whereHas('genres', function ($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }

        $sort = $request->input('sort', 'latest');

        switch ($sort) {
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

        $books = $query->paginate(10)->appends($request->all());

        $genres = Genre::withCount('books')->get();

        return view('books.index', compact('books', 'genres'));
    }

    /**
     * 書籍登録画面を表示（応用版PG03）
     * 【引数無しの正しいPHPDoc構造へ調整】
     */
    public function create(): View
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    /**
     * 書籍を新規登録
     * 【型宣言・PHPDoc完全対応】名前空間のタイポを厳密に補正
     */
    public function store(BookRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $imageUrl = $request->input('image_url');
        $titleKana = $request->input('title_kana');

        if ($imageUrl) {
            $imageUrl = strtok($imageUrl, '&');
        }

        $book = Book::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'title_kana' => ! empty($titleKana) ? $titleKana : $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'] ?? null,
            'published_date' => $request->input('published_date') ?? null,
            'description' => $validated['description'] ?? null,
            'image_url' => $imageUrl ?? null,
        ]);

        $book->genres()->sync($validated['genre_ids']);

        return redirect()->route('books.index')->with('success', '書籍を登録しました。');
    }

    /**
     * 書籍詳細画面を表示
     * 【型宣言・PHPDoc完全対応】
     */
    public function show(Book $book): View
    {
        $book->load(['genres', 'reviews.user']);

        return view('books.show', compact('book'));
    }

    /**
     * 書籍編集画面を表示
     * 【型宣言・PHPDoc完全対応】
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book);
        $genres = Genre::all();

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * 書籍情報を更新（保存処理）
     */
    public function update(BookRequest $request, Book $book): RedirectResponse
    {

        Gate::authorize('update', $book);

        $validated = $request->validated();

        $imageUrl = $request->input('image_url');
        $titleKana = $request->input('title_kana');

        if ($imageUrl) {
            $imageUrl = strtok($imageUrl, '&');
        }

        $book->update([
            'title' => $validated['title'],
            'title_kana' => ! empty($titleKana) ? $titleKana : $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'] ?? null,
            'published_date' => $request->input('published_date') ?? null,
            'description' => $validated['description'] ?? null,
            'image_url' => $imageUrl,
        ]);

        $book->genres()->sync($validated['genre_ids']);

        return redirect()->route('books.show', $book)->with('success', '書籍情報を更新しました。');
    }

    /**
     * 書籍を削除
     * 【型宣言・PHPDoc完全対応】DBファサードの名前空間をグローバル指定してクラッシュを完全回避
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        DB::transaction(function () use ($book) {
            $book->genres()->detach();
            $book->delete();
        });

        return redirect()->route('books.index')->with('success', '書籍を削除しました。');
    }

    /**
     * お気に入りの追加・解除（トグル）
     * 【型宣言・PHPDoc完全対応】
     */
    public function toggleFavorite(Book $book): RedirectResponse
    {
        auth()->user()->favoriteBooks()->toggle($book->id);

        return back()->with('success', 'お気に入りを更新しました。');
    }

    /**
     * レビューを投稿
     * 【型宣言・PHPDoc完全対応】名前空間のタイポを厳密に補正
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
     * 【型宣言・PHPDoc完全対応】引数のReview型を正しい名前空間へ補正
     */
    public function toggleLike(Review $review): RedirectResponse
    {
        auth()->user()->likedReviews()->toggle($review->id);

        return back();
    }

    /**
     * レビュー編集画面を表示
     * 【型宣言・PHPDoc完全対応】
     */
    public function editReview(Review $review): View
    {
        $this->authorize('update', $review);

        return view('reviews.edit', compact('review'));
    }

    /**
     * レビューの更新処理
     * 【型宣言・PHPDoc完全対応】引数の型とアノテーションの名前空間を厳密に同期
     */
    public function updateReview(ReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);
        $review->update($request->validated());

        return redirect()->route('books.show', $review->book_id)->with('success', 'レビューを更新しました。');
    }

    /**
     * レビューの削除処理
     * 【型宣言・PHPDoc完全対応】
     */
    public function destroyReview(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);
        $review->delete();

        return back()->with('success', 'レビューを削除しました。');
    }

    /**
     * お気に入り書籍一覧を表示
     * 【型宣言・PHPDoc完全対応】
     * 【Eager Loading / withCount による N+1 問題の完全回避】
     */
    public function favorites(): View
    {
        $books = auth()->user()->favoriteBooks()->with('genres')->paginate(10);

        $genres = Genre::withCount('books')->get();

        return view('favorites.index', compact('books', 'genres'));
    }

    /**
     * ランキング画面を表示
     * 【型宣言・PHPDoc完全対応】
     */
    public function ranking(): View
    {
        $rankedBooks = Book::has('reviews')
            ->withAvg('reviews', 'rating')
            ->orderBy('reviews_avg_rating', 'desc')
            ->limit(10)
            ->get();

        return view('ranking.index', compact('rankedBooks'));
    }

    /**
     * 外部API（Google Books）からISBNで書籍情報を取得してJSONで返す
     * 【型宣言・PHPDoc完全対応】
     * 【前半のガードと後半の画像・出版日データ抽出ロジックを一本に統合】
     */
    public function fetchByIsbn(string $isbn): JsonResponse
    {
        if (strlen($isbn) !== 13) {
            return response()->json(['error' => 'ISBNは13桁で入力してください。'], 422);
        }

        $cleanIsbn = trim($isbn);
        $apiKey = trim(env('GOOGLE_BOOKS_API_KEY'));

        $encodedIsbn = urlencode('isbn:'.$cleanIsbn);
        $targetUrl = "https://www.googleapis.com/books/v1/volumes?q={$encodedIsbn}&country=JP&key={$apiKey}";

        $response = Http::withoutVerifying()->get($targetUrl);

        if (! $response->successful() || ! isset($response->json()['items'])) {
            return response()->json(['error' => '書籍情報が見つかりませんでした。'], 404);
        }

        $bookData = $response->json()['items'][0]['volumeInfo'] ?? null;

        if (! $bookData) {
            return response()->json(['error' => '書籍詳細情報が取得できませんでした。'], 404);
        }

        $thumbnail = $bookData['imageLinks']['thumbnail'] ?? null;
        if ($thumbnail) {
            $thumbnail = str_replace('http://', 'https://', $thumbnail);
        }

        return response()->json([
            'title' => $bookData['title'] ?? '',
            'author' => isset($bookData['authors']) ? implode(', ', $bookData['authors']) : '',
            'description' => $bookData['description'] ?? '',
            'published_date' => $bookData['publishedDate'] ?? null,
            'image_url' => $thumbnail,
        ]);
    }
}
