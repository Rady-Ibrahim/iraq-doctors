<?php

namespace Modules\Review\Services\Api;

use App\Notifications\NewReviewSubmitted;
use App\Services\AdminNotificationService;
use Modules\Review\Models\Review;
use Modules\Appointment\Models\Appointment;
use Modules\Doctor\Models\Doctor;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function create(array $data): ?Review
    {
        return DB::transaction(function () use ($data) {
            $appointment = Appointment::findOrFail($data['appointment_id']);

            if ($appointment->status !== 'completed') {
                return null;
            }

            if ($appointment->patient_id !== $data['patient_id']) {
                return null;
            }

            if (Review::where('appointment_id', $data['appointment_id'])->exists()) {
                return null;
            }

            $review = Review::create([
                'appointment_id' => $data['appointment_id'],
                'doctor_id' => $appointment->doctor_id,
                'patient_id' => $data['patient_id'],
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
                'status' => Review::STATUS_PENDING,
            ]);

            AdminNotificationService::notify(new NewReviewSubmitted($review));

            return $review;
        });
    }

    public function getDoctorReviews(string $doctorId)
    {
        return Review::with(['patient'])
            ->where('doctor_id', $doctorId)
            ->approved()
            ->where('is_flagged', false)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getPatientReviews(string $patientId)
    {
        return Review::with(['doctor.user', 'doctor.speciality'])
            ->where('patient_id', $patientId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function approve(Review $review, int $adminId): Review
    {
        return DB::transaction(function () use ($review, $adminId) {
            if ($review->status === Review::STATUS_APPROVED) {
                return $review;
            }

            $review->update([
                'status' => Review::STATUS_APPROVED,
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
                'reject_reason' => null,
            ]);

            Doctor::findOrFail($review->doctor_id)->recalculateRatingFromReviews();

            return $review->fresh(['patient', 'doctor.user', 'doctor.speciality']);
        });
    }

    public function reject(Review $review, int $adminId, ?string $reason = null): Review
    {
        return DB::transaction(function () use ($review, $adminId, $reason) {
            $wasApproved = $review->status === Review::STATUS_APPROVED;

            $review->update([
                'status' => Review::STATUS_REJECTED,
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
                'reject_reason' => $reason,
            ]);

            if ($wasApproved) {
                Doctor::findOrFail($review->doctor_id)->recalculateRatingFromReviews();
            }

            return $review->fresh(['patient', 'doctor.user', 'doctor.speciality']);
        });
    }
}
