<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Laboratory\Models\Laboratory;

class LaboratoryDocumentsResubmitted extends Notification
{
    use Queueable;

    public function __construct(private Laboratory $laboratory) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->laboratory->loadMissing('user');

        return [
            'title' => 'إعادة رفع مستندات مختبر',
            'message' => 'أعاد مختبر ' . ($this->laboratory->name ?? '') . ' رفع المستندات للمراجعة',
            'type' => 'laboratory_resubmit',
            'laboratory_id' => $this->laboratory->id,
            'laboratory_name' => $this->laboratory->name,
            'action_url' => '/admin/dashboard/laboratories/' . $this->laboratory->id,
        ];
    }
}
