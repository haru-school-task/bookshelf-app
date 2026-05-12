<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Genre; // 追記：Genreモデルを使うために必要
use App\Http\Requests\BookRequest; // 追記：作った「盾」を使うために必要
use Illuminate\Http\Request;

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
}
