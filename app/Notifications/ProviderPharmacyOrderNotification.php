<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Pharmacy\Models\PharmacyOrder;

class ProviderPharmacyOrderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private PharmacyOrder $order,
        private string $title,
        private string $message,
        private string $type = 'pharmacy_order_provider'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->order->loadMissing('patient');

        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->order->status->value,
            'action_url' => '/pharmacy/dashboard/orders/' . $this->order->id,
        ];
    }
}
