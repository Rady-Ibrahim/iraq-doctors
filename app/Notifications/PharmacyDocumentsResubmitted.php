<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Pharmacy\Models\Pharmacy;

class PharmacyDocumentsResubmitted extends Notification
{
    use Queueable;

    public function __construct(private Pharmacy $pharmacy) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->pharmacy->loadMissing('user');

        return [
            'title' => 'إعادة مستندات صيدلية',
            'message' => 'أعادت صيدلية ' . ($this->pharmacy->name ?? '') . ' رفع المستندات للمراجعة',
            'type' => 'pharmacy_documents_resubmitted',
            'pharmacy_id' => $this->pharmacy->id,
            'pharmacy_name' => $this->pharmacy->name,
            'action_url' => '/admin/dashboard/pharmacies/' . $this->pharmacy->id,
        ];
    }
}
