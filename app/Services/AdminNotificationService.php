<?php

namespace App\Services;

use Illuminate\Notifications\Notification;
use Modules\Auth\Models\User;

class AdminNotificationService
{
    public static function notify(Notification $notification): void
    {
        User::query()
            ->where('role', 'admin')
            ->where('status', 'active')
            ->each(fn (User $admin) => $admin->notify($notification));
    }
}
