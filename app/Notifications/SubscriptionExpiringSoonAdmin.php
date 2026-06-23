<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Subscription\Models\DoctorSubscription;

class SubscriptionExpiringSoonAdmin extends Notification
{
    use Queueable;

    public function __construct(private DoctorSubscription $subscription) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->subscription->loadMissing(['doctor.user', 'subscription']);

        return [
            'title' => 'اشتراك على وشك الانتهاء',
            'message' => 'اشتراك د. ' . ($this->subscription->doctor?->user?->name ?? 'غير معروف')
                . ' في باقة ' . ($this->subscription->subscription?->name ?? '')
                . ' ينتهي خلال 3 أيام (' . $this->subscription->end_date?->format('Y-m-d') . ')',
            'type' => 'subscription_expiring',
            'subscription_id' => $this->subscription->id,
            'doctor_id' => $this->subscription->doctor_id,
            'action_url' => '/admin/dashboard/subscriptions',
        ];
    }
}
