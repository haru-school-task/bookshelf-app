<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Class NotificationController
 *
 * 通知（Notification）の管理および既読化制御を行うコントローラー
 */
class NotificationController extends Controller
{
    /**
     * PG18: 通知一覧の表示（DatabaseChannel連動）
     */
    public function index(): View
    {
        // ログインユーザーの「すべての通知（既読・未読すべて）」を取得してビューに渡す
        $notifications = auth()->user()->notifications;

        return view('notifications.index', compact('notifications'));
    }

    /**
     * 各通知の既読化アクション
     */
    public function markAsRead(string $id): RedirectResponse
    {
        // ユーザーの未読通知の中から、該当するIDの通知をピンポイントで検索
        $notification = auth()->user()->unreadNotifications->find($id);

        if ($notification) {
            // Laravel標準機能を使って、read_at に日付を入れて既読状態にする
            $notification->markAsRead();
        }

        return redirect()->back()->with('success', '通知を既読にしました。');
    }
}
