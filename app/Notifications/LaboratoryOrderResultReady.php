<?php

namespace App\Notifications;

use App\Traits\SendsPushNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Laboratory\Models\LaboratoryOrder;

class LaboratoryOrderResultReady extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(private LaboratoryOrder $order) {}

    public function via(object $notifiable): array
    {
        return $this->patientPushChannels();
    }

    public function toArray(object $notifiable): array
    {
        $this->order->loadMissing('laboratory');

        return [
            'title' => 'نتائج التحاليل جاهزة',
            'message' => 'نتائج طلبك رقم ' . $this->order->order_number
                . ' من ' . ($this->order->laboratory?->name ?? 'المعمل') . ' متاحة الآن.',
            'type' => 'laboratory_result_ready',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'laboratory_id' => $this->order->laboratory_id,
            'action_url' => '/laboratory-orders/' . $this->order->id,
        ];
    }
}
