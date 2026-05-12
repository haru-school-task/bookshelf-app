<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Genre; // 追記：Genreモデルを使うために必要
use App\Http\Requests\BookRequest; // 追記：作った「盾」を使うために必要
use App\Http\Requests\ReviewRequest; // 追記
use App\Models\Review;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // with('genres') を追加することで、本と一緒にジャンルデータを一括取得します（N+1対策）
        $books = Book::with('genres')->paginate(10);
        $genres = Genre::all();

        // ★重要：必ず view() を return する！
        return view('books.index', compact('books', 'genres'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // ジャンルを選択できるようにDBから全部取ってくる
        $genres = \App\Models\Genre::all();

        // 書籍登録画面（books/create.blade.php）を表示
        return view('books.create', compact('genres'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BookRequest $request)
    {
        // ここに来たときには、すでにバリデーションを通過した「綺麗なデータ」しか入っていません
        $validated = $request->validated();

        // 2. ログイン中のユーザーIDをセットして書籍を作成（Eloquentの活用）
        $book = \App\Models\Book::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'] ?? null,
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null, // 追加分
        ]);

        // 3. 中間テーブル（book_genre）に選択されたジャンルIDを紐付ける（プロの技：sync）
        $book->genres()->sync($validated['genre_ids']);

        // 4. 一覧画面へ戻し、「登録できたよ」という緑色の通知メッセージを添える
        return redirect()->route('books.index')->with('success', '書籍を登録しました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book)
    {
        // 本に紐づくジャンル、レビュー、そしてレビューを書いたユーザーを一気にロード
        $book->load(['genres', 'reviews.user']);

        // ★重要：詳細画面（books.show）のお皿を返す
        return view('books.show', compact('book'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Book $book)
    {
        // 編集画面を表示する前にチェック
        $this->authorize('update', $book);

        $genres = Genre::all();
        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BookRequest $request, Book $book) // 修正：Request -> BookRequest
    {
        // 実際にDBを更新する前にチェック
        $this->authorize('update', $book);

        $validated = $request->validated();

        // 3. Eloquent を活用して書籍情報を一気に更新
        $book->update([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        // 4. 中間テーブルのジャンル紐付けを、最新の状態に綺麗に上書き（sync）
        $book->genres()->sync($validated['genre_ids']);

        // 5. 一覧画面に戻し、緑色の成功メッセージを添える
        return redirect()->route('books.index')->with('success', '書籍情報を更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        // 削除する前にチェック
        $this->authorize('delete', $book);

        $book->delete();
        return redirect()->route('books.index')->with('success', '書籍を削除しました。');
    }

    public function storeReview(ReviewRequest $request, Book $book)
    {
        // すでにレビュー済みかチェック
        if ($book->reviews()->where('user_id', auth()->id())->exists()) {
            return back()->withErrors(['comment' => 'この本には既にレビューを投稿済みです。']);
        }

        $book->reviews()->create([
            'user_id' => auth()->id(),
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return redirect()->route('books.show', $book)->with('success', 'レビューを投稿しました！');
    }

    /**
     * お気に入りの追加・解除（トグル）
     */
    public function toggleFavorite(Book $book)
    {
        // toggleメソッドは、存在すれば削除、なければ追加を1行で行う魔法の関数です [INDEX1]
        auth()->user()->favoriteBooks()->toggle($book->id);

        return back()->with('success', 'お気に入りを更新しました。');
    }

    /**
     * レビューへの「いいね」
     */
    public function toggleLike(\App\Models\Review $review)
    {
        // ユーザーとレビューの「いいね」リレーションをトグル
        auth()->user()->likedReviews()->toggle($review->id);

        return back();
    }

    // app/Http/Controllers/BookController.php

    /**
     * レビュー編集画面の表示
     */
    public function editReview(Review $review)
    {
        // 【重要】投稿者本人かチェック（Policyは後ほど作成）
        $this->authorize('update', $review);

        // 編集用のお皿（reviews.edit）を出し、レビュー情報を渡す
        return view('reviews.edit', compact('review'));
    }

    /**
     * レビューの更新処理
     */
    public function updateReview(ReviewRequest $request, Review $review)
    {
        // 【重要】投稿者本人かチェック
        $this->authorize('update', $review);

        // 盾（ReviewRequest）を通過した安全なデータで更新
        $review->update($request->validated());

        // その本（親）の詳細画面に戻る
        return redirect()->route('books.show', $review->book_id)
            ->with('success', 'レビューを更新しました。');
    }

    /**
     * レビューの削除処理
     */
    public function destroyReview(Review $review)
    {
        // 1. 本人以外が不正に消そうとしたら 403 で弾く！
        // ※ReviewPolicyは先ほど作成済みなので、そのまま使えます
        $this->authorize('delete', $review);

        // 2. 削除実行
        $review->delete();

        // 3. 元の本の画面に戻る
        return back()->with('success', 'レビューを削除しました。');
    }

    public function favorites()
    {
        // ログイン中のユーザーが「お気に入り」している本を、10件ずつ取得
        $books = auth()->user()->favoriteBooks()->paginate(10);

        // サイドバーのジャンル一覧用
        $genres = \App\Models\Genre::all();

        // お気に入り一覧のお皿（favorites.index）を表示
        return view('favorites.index', compact('books', 'genres'));
    }

    public function ranking()
    {
        // 1. お気に入り（favorites）の数が多い順に上位10件を取得するプロのクエリ
        $rankedBooks = Book::withCount('favoriteUsers') // お気に入り数をカウント
            ->orderBy('favorite_users_count', 'desc') // カウントが多い順に並べる
            ->limit(10) // 上位10件に絞る
            ->get();

        // 2. ランキングのお皿（ranking.index）を表示
        return view('ranking.index', compact('rankedBooks'));
    }

}
