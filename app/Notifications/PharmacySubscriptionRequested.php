<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Pharmacy\Models\PharmacySubscription;

class PharmacySubscriptionRequested extends Notification
{
    use Queueable;

    public function __construct(private PharmacySubscription $subscription) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->subscription->loadMissing('pharmacy', 'subscription');

        return [
            'title' => 'طلب اشتراك صيدلية',
            'message' => 'طلب اشتراك من صيدلية ' . ($this->subscription->pharmacy?->name ?? ''),
            'type' => 'pharmacy_subscription_requested',
            'pharmacy_subscription_id' => $this->subscription->id,
            'pharmacy_id' => $this->subscription->pharmacy_id,
            'plan_name' => $this->subscription->subscription?->name,
            'action_url' => '/admin/dashboard/subscriptions',
        ];
    }
}
