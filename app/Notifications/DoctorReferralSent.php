<?php

namespace App\Notifications;

use App\Traits\SendsPushNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\MedicalRecord\Models\MedicalRecord;

class DoctorReferralSent extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(private MedicalRecord $record) {}

    public function via(object $notifiable): array
    {
        return $this->patientPushChannels();
    }

    public function toArray(object $notifiable): array
    {
        $this->record->loadMissing(['doctor.user', 'recommendedPharmacy', 'recommendedLaboratory']);

        $parts = [];
        if ($this->record->recommended_pharmacy_id) {
            $parts[] = 'صيدلية ' . ($this->record->recommendedPharmacy?->name ?? '');
        }
        if ($this->record->recommended_laboratory_id) {
            $parts[] = 'معمل ' . ($this->record->recommendedLaboratory?->name ?? '');
        }

        $target = $parts !== [] ? implode(' و', $parts) : 'مقدم الخدمة';

        return [
            'title' => 'إحالة جديدة من طبيبك',
            'message' => 'د. ' . ($this->record->doctor?->user?->name ?? 'طبيبك') . ' أرسل لك روشتة/تحاليل — يُنصح بـ ' . $target,
            'type' => 'doctor_referral',
            'prescription_id' => $this->record->id,
            'recommended_pharmacy_id' => $this->record->recommended_pharmacy_id,
            'recommended_laboratory_id' => $this->record->recommended_laboratory_id,
            'action_url' => '/prescriptions/' . $this->record->id,
        ];
    }
}
