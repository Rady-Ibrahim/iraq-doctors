<?php

namespace App\Notifications;

use App\Traits\SendsPushNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Laboratory\Enums\LaboratoryOrderStatus;
use Modules\Laboratory\Models\LaboratoryOrder;

class LaboratoryOrderStatusChanged extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(
        private LaboratoryOrder $order,
        private string $message
    ) {}

    public function via(object $notifiable): array
    {
        return $this->patientPushChannels();
    }

    public function toArray(object $notifiable): array
    {
        $this->order->loadMissing('laboratory');

        $isQuoted = $this->order->status === LaboratoryOrderStatus::Quoted;
        $total = $this->order->total_amount
            ? number_format((float) $this->order->total_amount, 0) . ' د.ع'
            : null;

        $data = [
            'title' => $isQuoted ? 'عرض سعر جاهز للموافقة' : 'تحديث طلب التحاليل',
            'message' => $isQuoted
                ? 'المعمل ' . ($this->order->laboratory?->name ?? '') . ' أرسل عرض سعر' . ($total ? ' بقيمة ' . $total : '') . '. راجع التفاصيل ووافق أو ارفض.'
                : $this->message,
            'type' => $isQuoted ? 'laboratory_order_quote_ready' : 'laboratory_order_status',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'laboratory_id' => $this->order->laboratory_id,
            'status' => $this->order->status->value,
            'action_url' => '/laboratory-orders/' . $this->order->id,
            'can_accept_quote' => $isQuoted,
            'can_cancel' => in_array($this->order->status, [
                LaboratoryOrderStatus::New,
                LaboratoryOrderStatus::Reviewing,
                LaboratoryOrderStatus::Quoted,
            ], true),
        ];

        if ($isQuoted) {
            $data['actions'] = [
                [
                    'type' => 'accept_quote',
                    'label' => 'موافقة على السعر',
                    'method' => 'POST',
                    'url' => '/api/v1/laboratory-orders/' . $this->order->id . '/accept-quote',
                ],
                [
                    'type' => 'cancel',
                    'label' => 'رفض الطلب',
                    'method' => 'POST',
                    'url' => '/api/v1/laboratory-orders/' . $this->order->id . '/cancel',
                ],
            ];
        }

        return $data;
    }
}
