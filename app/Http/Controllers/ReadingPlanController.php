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
 *
 * 
 * @package App\Http\Controllers
 */
class ReadingPlanController extends Controller
{
    /**
     * 読書計画の一覧および状態による絞り込み表示を行う
     * Eager Loading(with)によるN+1問題の回避を適切に実装
     * 
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $statusInput = $request->input('status');
        $currentStatus = null;

        if ($request->filled('status')) {
            $currentStatus = ReadingPlanStatus::tryFrom((int) $statusInput);
        }

        $query = ReadingPlan::where('user_id', auth()->id())->with('book');

        if (! is_null($currentStatus)) {
            $query->where('status', $currentStatus);
        }

        $readingPlans = $query->orderBy('target_date', 'asc')->get();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * 読書計画の新規作成画面を表示する
     * 
     * @return View
     */
    public function create(): View
    {
        $books = Book::where('user_id', auth()->id())->get();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 読書計画を新規保存する
     *  
     * @param Request $request
     * @return RedirectResponse
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
     * Policyによる認可処理
     *  
     * @param ReadingPlan $readingPlan
     * @return View
     */
    public function edit(ReadingPlan $readingPlan): View
    {
        Gate::authorize('update', $readingPlan);

        
        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画を更新する
     *
     * @param Request $request
     * @param ReadingPlan $readingPlan
     * @return RedirectResponse
     */
    public function update(Request $request, ReadingPlan $readingPlan): RedirectResponse
    {
        Gate::authorize('update', $readingPlan);

        $validated = $request->validate([
            'target_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $readingPlan->update([
            'target_date' => $validated['target_date'],
            'status' => ReadingPlanStatus::Reading->value,
        ]);

        return redirect()->route('reading-plans.index')
            ->with('success', '読書計画を更新し、読書中になりました。');
    }

    /**
     * 読書計画を削除する
     *
     * @param ReadingPlan $readingPlan
     * @return RedirectResponse
     */
    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        Gate::authorize('delete', $readingPlan);

        $readingPlan->delete();

        return redirect()->route('reading-plans.index')->with('success', '読書計画を削除しました。');
    }

    
    /**
     * 読書計画を完了状態にする
     * 
     * @param ReadingPlan $readingPlan
     * @return RedirectResponse
     */
    public function complete(ReadingPlan $readingPlan): RedirectResponse
    {
        Gate::authorize('complete', $readingPlan);

        // 1. 読書計画を完了にする
        $readingPlan->status = ReadingPlanStatus::Completed;
        $readingPlan->save();

        return redirect()->route('reading-plans.index')->with('success', '読書計画を完了にしました！');
    }

}
