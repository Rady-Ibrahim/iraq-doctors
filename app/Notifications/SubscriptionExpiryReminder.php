<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Subscription\Models\DoctorSubscription;

class SubscriptionExpiryReminder extends Notification
{
    use Queueable;

    public function __construct(private DoctorSubscription $subscription) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->subscription->loadMissing('subscription');

        return [
            'title' => 'تنبيه انتهاء الاشتراك',
            'message' => 'اشتراكك في باقة ' . ($this->subscription->subscription?->name ?? '')
                . ' ينتهي بتاريخ ' . $this->subscription->end_date?->format('Y-m-d')
                . ' — يرجى التجديد',
            'type' => 'subscription_expiry_reminder',
            'subscription_id' => $this->subscription->id,
            'action_url' => '/doctor/dashboard/subscription/plans',
        ];
    }
}
