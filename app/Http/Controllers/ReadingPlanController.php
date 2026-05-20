<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Enums\BookStatus;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 読書計画（ReadingPlan）を管理するコントローラー
 */
class ReadingPlanController extends Controller
{
    /**
     * PG15: 読書計画一覧（状態による絞り込み対応）
     * 
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        // 画面のセレクトボックスが待っている「現在の絞り込みステータス」を確保
        $currentStatus = $request->filled('status') ? (int)$request->input('status') : null;

        // ログインユーザーの読書計画を、書籍情報を含めて取得
        $query = ReadingPlan::where('user_id', auth()->id())->with('book');

        // 状態による絞り込み機能
        if (!is_null($currentStatus)) {
            $query->where('status', $currentStatus);
        }

        // ★【変数名を修正】 $plans から $readingPlans に書き換えて公式Bladeとミリ単位で同期させます！
        $readingPlans = $query->orderBy('target_date', 'asc')->get();

        // ⭕ 公式Bladeが探している「$readingPlans」と「$currentStatus」を compact に込めてパスします！
        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * PG16: 読書計画作成画面（書籍プルダウン表示用）
     * 
     * @return View
     */
    public function create(): View
    {
        // 💡 プルダウン用に、登録されているすべての書籍を取得します
        $books = Book::orderBy('title', 'asc')->get();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 読書計画の保存処理
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        // 🛡️ 門番（バリデーション）：書籍の存在チェック、未来日の期日チェック
        $validated = $request->validate([
            'book_id'     => ['required', 'exists:books,id'],
            'target_date' => ['required', 'date', 'after_or_equal:today'],
        ], [
            'book_id.required'     => '書籍を選択してください。',
            'target_date.required' => '目標期日を入力してください。',
            'target_date.date'     => '正しい日付形式で入力してください。',
            'target_date.after_or_equal' => '目標期日には今日以降の日付を指定してください。',
        ]);

        // データベースへ安全に保存（初期状態は 1:未着手）
        ReadingPlan::create([
            'user_id'     => auth()->id(),
            'book_id'     => $validated['book_id'],
            'target_date' => $validated['target_date'],
            'status'      => BookStatus::UNREAD->value, // Enumの「1:未着手」を注入
        ]);

        return redirect()->route('reading-plans.index')->with('success', '読書計画を作成しました。');
    }

    /**
     * PG17: 読書計画編集画面（期日変更フォーム用）
     * 
     * @param ReadingPlan $readingPlan
     * @return View
     */
    public function edit(ReadingPlan $readingPlan): View
    {
        // 🛡️ 認可判定：自分以外の計画を編集しようとしたら403エラーで完璧に弾きます！
        if ($readingPlan->user_id !== auth()->id()) {
            abort(403, 'この読書計画の編集権限がありません。');
        }

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画の更新処理
     * 
     * @param Request $request
     * @param ReadingPlan $readingPlan
     * @return RedirectResponse
     */
    public function update(Request $request, ReadingPlan $readingPlan): RedirectResponse
    {
        // 🛡️ 認可判定
        if ($readingPlan->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'target_date' => ['required', 'date'],
            'status'      => ['required', 'integer', 'in:1,2,3'], // 1=未着手, 2=読書中, 3=読了
        ]);

        // 状態と期日を一斉にアップデート
        $readingPlan->update([
            'target_date' => $validated['target_date'],
            'status'      => $validated['status'],
        ]);

        return redirect()->route('reading-plans.index')->with('success', '読書計画を更新しました。');
    }

    /**
     * 読書計画の削除処理
     * 
     * @param ReadingPlan $readingPlan
     * @return RedirectResponse
     */
    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        // 🛡️ 認可判定
        if ($readingPlan->user_id !== auth()->id()) {
            abort(403);
        }

        $readingPlan->delete();

        return redirect()->route('reading-plans.index')->with('success', '読書計画を削除しました。');
    }
}
