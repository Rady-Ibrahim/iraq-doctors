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
        $this->review->loadMissing('patient', 'doctor.user');

        return [
            'title' => 'تقييم جديد',
            'message' => 'أضاف ' . ($this->review->patient?->name ?? 'مريض') . ' تقييماً لد. ' . ($this->review->doctor?->user?->name ?? 'غير معروف') . ' — بانتظار الموافقة',
            'type' => 'new_review',
            'review_id' => $this->review->id,
            'doctor_id' => $this->review->doctor_id,
            'rating' => $this->review->rating,
            'action_url' => '/admin/dashboard/reviews?status=pending',
        ];
    }
}
