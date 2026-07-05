<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Laboratory\Models\Laboratory;

class NewLaboratoryRegistered extends Notification
{
    use Queueable;

    public function __construct(private Laboratory $laboratory) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->laboratory->loadMissing('user', 'governorate');

        return [
            'title' => 'معمل جديد',
            'message' => 'سجّل معمل ' . ($this->laboratory->name ?? 'غير معروف') . ' وينتظر الموافقة',
            'type' => 'new_laboratory',
            'laboratory_id' => $this->laboratory->id,
            'laboratory_name' => $this->laboratory->name,
            'owner_name' => $this->laboratory->user?->name,
            'governorate' => $this->laboratory->governorate?->name_ar,
            'action_url' => '/admin/dashboard/laboratories/' . $this->laboratory->id,
        ];
    }
}
