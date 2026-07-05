<?php

namespace Modules\Auth\Services\Api;

use Illuminate\Notifications\DatabaseNotification;
use Modules\Auth\Models\User;

class PatientNotificationService
{
    public function list(User $user, bool $unreadOnly = false, int $limit = 30): array
    {
        $query = $user->notifications()->latest();

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        $notifications = $query->limit($limit)->get();

        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $notifications->map(fn ($n) => $this->format($n))->values()->all(),
        ];
    }

    public function markRead(User $user, string $notificationId): void
    {
        $notification = $user->notifications()->where('id', $notificationId)->firstOrFail();
        $notification->markAsRead();
    }

    public function markAllRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }

    protected function format(DatabaseNotification $notification): array
    {
        $data = $notification->data ?? [];

        return [
            'id' => $notification->id,
            'title' => $data['title'] ?? 'إشعار',
            'message' => $data['message'] ?? '',
            'type' => $data['type'] ?? 'general',
            'action_url' => $data['action_url'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'order_number' => $data['order_number'] ?? null,
            'status' => $data['status'] ?? null,
            'can_accept_quote' => (bool) ($data['can_accept_quote'] ?? false),
            'can_cancel' => (bool) ($data['can_cancel'] ?? false),
            'actions' => $data['actions'] ?? [],
            'read_at' => $notification->read_at?->format('Y-m-d H:i'),
            'created_at' => $notification->created_at?->format('Y-m-d H:i'),
        ];
    }
}
