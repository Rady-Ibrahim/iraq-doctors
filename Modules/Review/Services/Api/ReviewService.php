<?php

namespace Modules\Review\Services\Api;

use App\Notifications\NewReviewSubmitted;
use App\Services\AdminNotificationService;
use Illuminate\Support\Facades\DB;
use Modules\Appointment\Models\Appointment;
use Modules\Doctor\Models\Doctor;
use Modules\Laboratory\Enums\LaboratoryOrderStatus;
use Modules\Laboratory\Models\Laboratory;
use Modules\Laboratory\Models\LaboratoryOrder;
use Modules\Pharmacy\Enums\PharmacyOrderStatus;
use Modules\Pharmacy\Models\Pharmacy;
use Modules\Pharmacy\Models\PharmacyOrder;
use Modules\Review\Models\Review;

class ReviewService
{
    public function create(array $data): ?Review
    {
        if (! empty($data['pharmacy_order_id'])) {
            return $this->createForPharmacyOrder($data);
        }

        if (! empty($data['laboratory_order_id'])) {
            return $this->createForLaboratoryOrder($data);
        }

        return $this->createForAppointment($data);
    }

    public function createForAppointment(array $data): ?Review
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

    public function createForPharmacyOrder(array $data): ?Review
    {
        return DB::transaction(function () use ($data) {
            $order = PharmacyOrder::findOrFail($data['pharmacy_order_id']);

            if ($order->status !== PharmacyOrderStatus::Completed) {
                return null;
            }

            if ($order->patient_id !== $data['patient_id']) {
                return null;
            }

            if (Review::where('pharmacy_order_id', $order->id)->exists()) {
                return null;
            }

            $review = Review::create([
                'pharmacy_order_id' => $order->id,
                'pharmacy_id' => $order->pharmacy_id,
                'patient_id' => $data['patient_id'],
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
                'status' => Review::STATUS_PENDING,
            ]);

            AdminNotificationService::notify(new NewReviewSubmitted($review));

            return $review;
        });
    }

    public function createForLaboratoryOrder(array $data): ?Review
    {
        return DB::transaction(function () use ($data) {
            $order = LaboratoryOrder::findOrFail($data['laboratory_order_id']);

            if ($order->status !== LaboratoryOrderStatus::Delivered) {
                return null;
            }

            if ($order->patient_id !== $data['patient_id']) {
                return null;
            }

            if (Review::where('laboratory_order_id', $order->id)->exists()) {
                return null;
            }

            $review = Review::create([
                'laboratory_order_id' => $order->id,
                'laboratory_id' => $order->laboratory_id,
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

    public function getPharmacyReviews(int $pharmacyId)
    {
        return Review::with(['patient'])
            ->where('pharmacy_id', $pharmacyId)
            ->approved()
            ->where('is_flagged', false)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getLaboratoryReviews(int $laboratoryId)
    {
        return Review::with(['patient'])
            ->where('laboratory_id', $laboratoryId)
            ->approved()
            ->where('is_flagged', false)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getPatientReviews(string $patientId)
    {
        return Review::with(['doctor.user', 'doctor.speciality', 'pharmacy', 'laboratory'])
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

            $this->recalculateRatingForReview($review);

            return $review->fresh(['patient', 'doctor.user', 'doctor.speciality', 'pharmacy', 'laboratory']);
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
                $this->recalculateRatingForReview($review);
            }

            return $review->fresh(['patient', 'doctor.user', 'doctor.speciality', 'pharmacy', 'laboratory']);
        });
    }

    protected function recalculateRatingForReview(Review $review): void
    {
        if ($review->doctor_id) {
            Doctor::findOrFail($review->doctor_id)->recalculateRatingFromReviews();

            return;
        }

        if ($review->pharmacy_id) {
            Pharmacy::findOrFail($review->pharmacy_id)->recalculateRatingFromReviews();

            return;
        }

        if ($review->laboratory_id) {
            Laboratory::findOrFail($review->laboratory_id)->recalculateRatingFromReviews();
        }
    }
}
