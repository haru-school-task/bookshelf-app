<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class NotificationPolicy
{
    /**
     * 指定された通知のステータス変更（既読化）について、ログインユーザーが所有者であるか認可判定を行います。
     *
     * @param  User  $user  認証済みのログインユーザーインスタンス
     * @param  DatabaseNotification  $notification  操作対象の通知インスタンス
     * @return bool 認可に成功した場合は true、それ以外は false
     */
    public function update(User $user, DatabaseNotification $notification): bool
    {
        return $user->id === (int) $notification->notifiable_id;
    }
}
