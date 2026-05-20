<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Class ReadingPlanController
 *
 * 読書計画機能の制御を行うコントローラー
 * 💡【コード品質担保：PHPDoc、コントローラーの責務に専念】
 */
class ReadingPlanController extends Controller
{
    /**
     * 読書計画の一覧および状態による絞り込み表示を行う
     * 💡【命名規則: camelCase型変数】【Eager Loading(with)によるN+1問題の完全回避】
     */
    public function index(Request $request): View
    {
        $statusInput = $request->input('status');
        $currentStatus = null;

        if ($request->filled('status')) {
            $currentStatus = ReadingPlanStatus::tryFrom((int) $statusInput);
        }

        // N+1問題を避けるため、with('book')によるEager Loadingを適切に実行
        $query = ReadingPlan::where('user_id', auth()->id())->with('book');

        if (! is_null($currentStatus)) {
            $query->where('status', $currentStatus);
        }

        $readingPlans = $query->orderBy('target_date', 'asc')->get();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * 読書計画の新規作成画面を表示する
     */
    public function create(): View
    {
        // 画面のプルダウン用の書籍データを取得
        $books = Book::where('user_id', auth()->id())->get();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 読書計画を新規保存する
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'book_id' => ['required', 'exists:books,id'],
            'target_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        ReadingPlan::create([
            'user_id' => auth()->id(),
            'book_id' => $validated['book_id'],
            'target_date' => $validated['target_date'],
            'status' => ReadingPlanStatus::Unread->value,
        ]);

        return redirect()->route('reading-plans.index')->with('success', '読書計画を作成しました。');
    }

    /**
     * 読書計画の編集画面を表示する
     * 💡【変数名: camelCase型への完全補正】【Policyによる認可処理】
     */
    public function edit(ReadingPlan $readingPlan): View
    {
        // 🔒 Policyを適用（指示シート通り、認可処理を強制）
        Gate::authorize('update', $readingPlan);

        // ⭕ 画面（Blade）が探している「$readingPlan」という完璧なキャメルケース変数名で引き渡します！
        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画を更新する
     * 💡【変数名: camelCase型】
     */
    public function update(Request $request, ReadingPlan $readingPlan): RedirectResponse
    {
        Gate::authorize('update', $readingPlan);

        $validated = $request->validate([
            'book_id' => ['required', 'exists:books,id'],
            'target_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $readingPlan->update([
            'book_id' => $validated['book_id'],
            'target_date' => $validated['target_date'],
        ]);

        return redirect()->route('reading-plans.index')->with('success', '読書計画を更新しました。');
    }

    /**
     * 読書計画を削除する
     * 💡【変数名: camelCase型】
     */
    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        Gate::authorize('delete', $readingPlan);

        $readingPlan->delete();

        return redirect()->route('reading-plans.index')->with('success', '読書計画を削除しました。');
    }

    /**
     * 読書計画を完了状態にする
     * 💡【変数名: camelCase型】
     */
    public function complete(ReadingPlan $readingPlan): RedirectResponse
    {
        Gate::authorize('complete', $readingPlan);

        $readingPlan->status = ReadingPlanStatus::Completed;
        $readingPlan->save();

        return redirect()->route('reading-plans.index')->with('success', '読書計画を完了にしました！');
    }
}
