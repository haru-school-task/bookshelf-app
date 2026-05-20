<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 通知（Notification）を管理するコントローラー
 */
class NotificationController extends Controller
{
    /**
     * PG18: 通知一覧の表示（DatabaseChannel連動）
     * 
     * @return View
     */
    public function index(): View
    {
        // 💡 ログインユーザーの「すべての通知（既読・未読すべて）」を取得してお皿に渡します
        $notifications = auth()->user()->notifications;

        return view('notifications.index', compact('notifications'));
    }

    /**
     * 各通知の既読化アクション
     * 
     * @param string $id
     * @return RedirectResponse
     */
    public function markAsRead(string $id): RedirectResponse
    {
        // 💡 ユーザーの未読通知の中から、該当するIDの通知をピンポイントで検索
        $notification = auth()->user()->unreadNotifications->find($id);

        if ($notification) {
            // ★【要件完全一致】Laravel標準機能を使って、一瞬で read_at に日付を入れて既読状態にします！
            $notification->markAsRead();
        }

        return redirect()->back()->with('success', '通知を既読にしました。');
    }
}

