<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Http\Requests\BookRequest;
use App\Http\Resources\Api\V1\BookResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class BookController extends Controller
{
    /**
     * 書籍一覧を取得（JSON形式、検索・ページネーション対応）
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Book::with('genres')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating');

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%");
            });
        }

        $books = $query->paginate(10);

        return BookResource::collection($books);
    }

    /**
     * 書籍詳細を取得
     * 正常時は BookResource、404エラー時は JsonResponse を返すため「|」で繋ぎます
     */
    public function show(string $id): BookResource|JsonResponse
    {
        $book = Book::with('genres')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->find($id);

        if (!$book) {
            return response()->json(['message' => '指定された書籍が見つかりません。'], Response::HTTP_NOT_FOUND);
        }

        return new BookResource($book);
    }

    /**
     * 書籍を新規登録
     */
    public function store(BookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $book = Book::create([
            'user_id' => auth()->id() ?? 1,
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        $book->genres()->sync($validated['genre_ids']);

        $book->load('genres')->loadCount('reviews')->loadAvg('reviews', 'rating');

        // 201 Created ステータスを持つレスポンスを返却
        return (new BookResource($book))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * 書籍情報を更新（API版）
     */
    public function update(BookRequest $request, string $id): BookResource|JsonResponse
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['message' => '指定された書籍が見つかりません。'], Response::HTTP_NOT_FOUND);
        }

        // ★最重要要件：API経由でも「所有者本人か」を厳格に認可する門番を発動！ [INDEX1]
        $this->authorize('update', $book);

        $validated = $request->validated();
        $book->update($validated);
        $book->genres()->sync($validated['genre_ids']);

        $book->load('genres')->loadCount('reviews')->loadAvg('reviews', 'rating');
        return new BookResource($book);
    }

    /**
     * 書籍を削除（API版）
     */
    public function destroy(string $id): JsonResponse
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['message' => '指定された書籍が見つかりません。'], Response::HTTP_NOT_FOUND);
        }

        // ★最重要要件：API経由でも「所有者本人か」を厳格に認可する門番を発動！ [INDEX1]
        $this->authorize('delete', $book);

        $book->delete();
        return response()->json(['message' => '書籍を削除しました。'], Response::HTTP_OK);
    }


}
