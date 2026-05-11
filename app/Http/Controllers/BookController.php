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
        $books = Book::paginate(10);
        $genres = \App\Models\Genre::all();

        // ★重要：必ず view() を return する！
        return view('books.index', compact('books', 'genres'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BookRequest $request)
    {
        // ここに来たときには、すでにバリデーションを通過した「綺麗なデータ」しか入っていません
        $validated = $request->validated();
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book)
    {
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
        // ここから更新ロジック...
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        // 削除する前にチェック
        $this->authorize('delete', $book);

        $book->delete();
        return redirect()->route('books.index');
    }
}
