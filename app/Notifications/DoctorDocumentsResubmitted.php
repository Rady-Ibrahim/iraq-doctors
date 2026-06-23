<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Doctor\Models\Doctor;

class DoctorDocumentsResubmitted extends Notification
{
    use Queueable;

    public function __construct(private Doctor $doctor) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->doctor->loadMissing('user');

        return [
            'title' => 'إعادة رفع مستندات',
            'message' => 'أعاد د. ' . ($this->doctor->user?->name ?? 'غير معروف') . ' رفع المستندات للمراجعة',
            'type' => 'doctor_resubmit',
            'doctor_id' => $this->doctor->id,
            'action_url' => '/admin/dashboard/doctors/' . $this->doctor->id,
        ];
    }
}
