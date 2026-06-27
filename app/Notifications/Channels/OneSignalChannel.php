<?php

namespace App\Notifications\Channels;

use App\Services\OneSignalService;
use Illuminate\Notifications\Notification;

class OneSignalChannel
{
    public function __construct(private OneSignalService $oneSignal) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (!OneSignalService::isEnabled()) {
            return;
        }

        if (!method_exists($notifiable, 'isPatient') || !$notifiable->isPatient()) {
            return;
        }

        if (!method_exists($notification, 'toArray')) {
            return;
        }

        $payload = $notification->toArray($notifiable);

        $title = $payload['title'] ?? 'إشعار';
        $message = $payload['message'] ?? '';

        if ($message === '') {
            return;
        }

        $this->oneSignal->sendToUser($notifiable, $title, $message, $payload);
    }
}
