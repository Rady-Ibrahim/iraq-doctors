<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Laboratory\Models\LaboratorySubscription;

class LaboratorySubscriptionRequested extends Notification
{
    use Queueable;

    public function __construct(private LaboratorySubscription $subscription) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->subscription->loadMissing(['laboratory.user', 'subscription']);

        return [
            'title' => 'طلب اشتراك مختبر جديد',
            'message' => ($this->subscription->laboratory?->name ?? 'مختبر')
                . ' طلب الاشتراك في باقة ' . ($this->subscription->subscription?->name ?? ''),
            'type' => 'laboratory_subscription_request',
            'subscription_id' => $this->subscription->id,
            'laboratory_id' => $this->subscription->laboratory_id,
            'subscriber_type' => 'laboratory',
            'action_url' => '/admin/dashboard/subscriptions',
        ];
    }
}
