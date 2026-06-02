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
        $notifications = auth()->user()->notifications;

        return view('notifications.index', compact('notifications'));
    }

    /**
     * 各通知の既読化アクション
     */
    public function markAsRead(string $id): RedirectResponse
    {
        $notification = auth()->user()->unreadNotifications->find($id);

        if ($notification) {
            $notification->markAsRead();
        }

        return redirect()->back()->with('success', '通知を既読にしました。');
    }
}
