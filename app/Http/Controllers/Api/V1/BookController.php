<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Http\Requests\BookRequest;
use App\Http\Resources\Api\V1\BookResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BookController extends Controller
{
    /**
     * 要件：書籍一覧を取得する（JSON形式、検索・ページネーション対応）
     */
    public function index(Request $request)
    {
        // 1. 要件である「ジャンル情報」「平均評点」「レビュー件数」を効率よく一括取得（N+1対策） [INDEX1]
        $query = Book::with('genres')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating');

        // 2. 要件：検索対応（タイトルまたは著者名での簡易検索例）
        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%");
            });
        }

        // 3. 要件：ページネーション対応（1ページ10件） [INDEX2]
        $books = $query->paginate(10);

        // 詰め替え箱を通してJSONとして返す
        return BookResource::collection($books);
    }

    /**
     * 要件：書籍詳細を取得する
     */
    public function show($id)
    {
        // 要件：存在しないIDの場合はエラーレスポンスを返す
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
     * 要件：書籍を新規登録する
     */
    public function store(BookRequest $request)
    {
        // Web側の盾（BookRequest）がここでも自動発動。失敗時はLaravelが自動で422エラーを返します
        $validated = $request->validated();

        $book = Book::create([
            'user_id' => auth()->id() ?? 1, // ★Sanctum未導入時は一旦デバッグ用にID:1をセット
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        $book->genres()->sync($validated['genre_ids']);

        // リレーションと集計をロードして返す
        $book->load('genres')->loadCount('reviews')->loadAvg('reviews', 'rating');
        return (new BookResource($book))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * 要件：書籍を更新する
     */
    public function update(BookRequest $request, $id)
    {
        $book = Book::find($id);

        // 要件：存在しないIDの場合はエラーレスポンスを返す
        if (!$book) {
            return response()->json(['message' => '指定された書籍が見つかりません。'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validated();
        $book->update($validated);
        $book->genres()->sync($validated['genre_ids']);

        $book->load('genres')->loadCount('reviews')->loadAvg('reviews', 'rating');
        return new BookResource($book);
    }

    /**
     * 要件：書籍を削除する
     */
    public function destroy($id)
    {
        $book = Book::find($id);

        // 要件：存在しないIDの場合はエラーレスポンスを返す
        if (!$book) {
            return response()->json(['message' => '指定された書籍が見つかりません。'], Response::HTTP_NOT_FOUND);
        }

        // 関連データはDBの「カスケード削除」によって適切に自動処理されます
        $book->delete();

        return response()->json(['message' => '書籍を削除しました。'], Response::HTTP_OK);
    }

}
