<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GenreController extends Controller
{
    public function __construct()
    {
        // ✅ 修正：except を消してこの1行だけにします。
        // これにより、一覧（index）や詳細（show）も含め、ジャンル関連はすべてログインしないと絶対に入れない鉄壁のガードに戻ります！
        $this->middleware('auth');
    }

    /**
     * ジャンル管理の一覧画面を表示（冊数カウント対応版）
     */
    public function index(): View
    {
        // ★修正点：各ジャンルに紐づく書籍（books）の数を自動集計して取得 [INDEX2]
        // これにより、画面側で $genre->books_count という名前で冊数が取り出せるようになります
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
     */
    public function show(Genre $genre): View
    {
        $books = $genre->books()->paginate(10);

        // ★修正点：ここもサイドバーなどの表示用に withCount を添えて全ジャンルを取得します
        $genres = Genre::withCount('books')->get();

        return view('genres.show', compact('genre', 'books', 'genres'));
    }

    /**
     * ★【新規追記！】ジャンル編集画面を表示
     */
    public function edit(Genre $genre): View
    {
        // 編集画面のお皿（genres.edit）に、対象のジャンルデータを渡して表示 [INDEX1]
        return view('genres.edit', compact('genre'));
    }

    /**
     * ★【新規追記！】ジャンル情報を更新（保存処理）
     */
    public function update(Request $request, Genre $genre): RedirectResponse
    {
        // 1. バリデーションの盾（自分自身の名前は重複OKにするプロのユニーク制約） [INDEX2]
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:genres,name,'.$genre->id],
        ]);

        // 2. データベースの値を更新
        $genre->update([
            'name' => $validated['name'],
        ]);

        // 3. ジャンル一覧画面へリダイレクト
        return redirect()->route('genres.index')->with('success', 'ジャンル名を更新しました。');
    }

    /**
     * ★【最終兵器！】ジャンルを削除
     */
    public function destroy(Genre $genre): RedirectResponse
    {
        // ★仕様要件：書籍の紐付きがある場合は削除を制限する
        if ($genre->books()->exists()) {
            return back()->withErrors(['error' => 'このジャンルには書籍が登録されているため、削除できません。']);
        }

        $genre->delete();

        return redirect()->route('genres.index')->with('success', 'ジャンルを削除しました。');
    }
}
