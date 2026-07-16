<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Review\Models\Review;

class NewReviewSubmitted extends Notification
{
    use Queueable;

    public function __construct(private Review $review) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->review->loadMissing('patient', 'doctor.user', 'pharmacy', 'laboratory');

        $providerLabel = 'د. ' . ($this->review->doctor?->user?->name ?? 'غير معروف');
        if ($this->review->pharmacy_id) {
            $providerLabel = 'صيدلية ' . ($this->review->pharmacy?->name ?? 'غير معروف');
        } elseif ($this->review->laboratory_id) {
            $providerLabel = 'مختبر ' . ($this->review->laboratory?->name ?? 'غير معروف');
        }

        return [
            'title' => 'تقييم جديد',
            'message' => 'أضاف ' . ($this->review->patient?->name ?? 'مريض') . ' تقييماً لـ' . $providerLabel . ' — بانتظار الموافقة',
            'type' => 'new_review',
            'review_id' => $this->review->id,
            'doctor_id' => $this->review->doctor_id,
            'pharmacy_id' => $this->review->pharmacy_id,
            'laboratory_id' => $this->review->laboratory_id,
            'rating' => $this->review->rating,
            'action_url' => '/admin/dashboard/reviews?status=pending',
        ];
    }
}
