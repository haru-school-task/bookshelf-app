<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class GenreController
 *
 * ジャンル管理機能（一覧、作成、詳細表示、編集、更新、削除制限）の制御を行うコントローラー
 */
class GenreController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // 一覧や詳細も含め、ジャンル関連はすべてログイン必須
        $this->middleware('auth');
    }

    /**
     * ジャンル管理の一覧画面を表示（冊数カウント対応）
     */
    public function index(): View
    {
        // 各ジャンルに紐づく書籍（books）の数を自動集計して一括取得
        $genres = Genre::withCount('books')->get();

        return view('genres.index', compact('genres'));
    }

    /**
     * ジャンル登録画面を表示
     */
    public function create(): View
    {
        return view('genres.create');
    }

    /**
     * ジャンルを新規登録
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:genres,name'],
        ]);

        Genre::create([
            'name' => $validated['name'],
        ]);

        return redirect()->route('genres.index')->with('success', '新しいジャンルを登録しました。');
    }

    /**
     * 特定のジャンルに紐づく書籍一覧（詳細）を表示
     *【10件/ページのページネーション対応】
     */
    public function show(Genre $genre): View
    {
        // 仕様書の「10件/ページで表示されること」に完全同期
        $books = $genre->books()->paginate(10);

        // サイドバーなどの表示用に withCount を添えて全ジャンルを取得
        $genres = Genre::withCount('books')->get();

        return view('genres.show', compact('genre', 'books', 'genres'));
    }

    /**
     * ジャンル編集画面を表示
     */
    public function edit(Genre $genre): View
    {
        return view('genres.edit', compact('genre'));
    }

    /**
     * ジャンル情報を更新（保存処理）
     */
    public function update(Request $request, Genre $genre): RedirectResponse
    {
        // 更新時は、同じ名前のジャンルが存在しても自分自身は除外してユニークチェックを行う
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:genres,name,'.$genre->id],
        ]);

        $genre->update([
            'name' => $validated['name'],
        ]);

        return redirect()->route('genres.index')->with('success', 'ジャンル名を更新しました。');
    }

    /**
     * ジャンルを削除
     *【書籍紐付き時の削除制限】
     */
    public function destroy(Genre $genre): RedirectResponse
    {
        // 書籍の紐付きがある場合は削除を制限して安全にブロック
        if ($genre->books()->exists()) {
            return back()->withErrors(['error' => 'このジャンルには書籍が登録されているため、削除できません。']);
        }

        $genre->delete();

        return redirect()->route('genres.index')->with('success', 'ジャンルを削除しました。');
    }
}
