<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Subscription\Models\DoctorSubscription;

class DoctorSubscriptionRequested extends Notification
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
            'title' => 'طلب اشتراك جديد',
            'message' => 'د. ' . ($this->subscription->doctor?->user?->name ?? 'غير معروف')
                . ' طلب الاشتراك في باقة ' . ($this->subscription->subscription?->name ?? ''),
            'type' => 'subscription_request',
            'subscription_id' => $this->subscription->id,
            'doctor_id' => $this->subscription->doctor_id,
            'action_url' => '/admin/dashboard/subscriptions',
        ];
    }
}
