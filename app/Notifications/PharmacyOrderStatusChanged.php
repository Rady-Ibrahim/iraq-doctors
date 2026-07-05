<?php

namespace App\Notifications;

use App\Traits\SendsPushNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Pharmacy\Enums\PharmacyOrderStatus;
use Modules\Pharmacy\Models\PharmacyOrder;

class PharmacyOrderStatusChanged extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(
        private PharmacyOrder $order,
        private string $message
    ) {}

    public function via(object $notifiable): array
    {
        return $this->patientPushChannels();
    }

    public function toArray(object $notifiable): array
    {
        $this->order->loadMissing('pharmacy');

        $isQuoted = $this->order->status === PharmacyOrderStatus::Quoted;
        $total = $this->order->total_amount
            ? number_format((float) $this->order->total_amount, 0) . ' د.ع'
            : null;

        $data = [
            'title' => $isQuoted ? 'عرض سعر جاهز للموافقة' : 'تحديث طلب الأدوية',
            'message' => $isQuoted
                ? 'الصيدلية ' . ($this->order->pharmacy?->name ?? '') . ' أرسلت عرض سعر' . ($total ? ' بقيمة ' . $total : '') . '. راجع التفاصيل ووافق أو ارفض.'
                : $this->message,
            'type' => $isQuoted ? 'pharmacy_order_quote_ready' : 'pharmacy_order_status',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'pharmacy_id' => $this->order->pharmacy_id,
            'status' => $this->order->status->value,
            'action_url' => '/pharmacy-orders/' . $this->order->id,
            'can_accept_quote' => $isQuoted,
            'can_cancel' => in_array($this->order->status, [
                PharmacyOrderStatus::New,
                PharmacyOrderStatus::Reviewing,
                PharmacyOrderStatus::Quoted,
            ], true),
        ];

        if ($isQuoted) {
            $data['actions'] = [
                [
                    'type' => 'accept_quote',
                    'label' => 'موافقة على السعر',
                    'method' => 'POST',
                    'url' => '/api/v1/pharmacy-orders/' . $this->order->id . '/accept-quote',
                ],
                [
                    'type' => 'cancel',
                    'label' => 'رفض الطلب',
                    'method' => 'POST',
                    'url' => '/api/v1/pharmacy-orders/' . $this->order->id . '/cancel',
                ],
            ];
        }

        return $data;
    }
}
