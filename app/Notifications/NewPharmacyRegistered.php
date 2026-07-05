<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Pharmacy\Models\Pharmacy;

class NewPharmacyRegistered extends Notification
{
    use Queueable;

    public function __construct(private Pharmacy $pharmacy) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->pharmacy->loadMissing('user', 'governorate');

        return [
            'title' => 'صيدلية جديدة',
            'message' => 'سجّلت صيدلية ' . ($this->pharmacy->name ?? 'غير معروفة') . ' وتنتظر الموافقة',
            'type' => 'new_pharmacy',
            'pharmacy_id' => $this->pharmacy->id,
            'pharmacy_name' => $this->pharmacy->name,
            'owner_name' => $this->pharmacy->user?->name,
            'governorate' => $this->pharmacy->governorate?->name_ar,
            'action_url' => '/admin/dashboard/pharmacies/' . $this->pharmacy->id,
        ];
    }
}
