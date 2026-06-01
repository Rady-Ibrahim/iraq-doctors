<?php

namespace Modules\Review\Services\Api;

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
            ]);

            $doctor = Doctor::findOrFail($appointment->doctor_id);
            $doctor->updateRating($data['rating']);

            return $review;
        });
    }

    public function getDoctorReviews(string $doctorId)
    {
        return Review::with(['patient'])
            ->where('doctor_id', $doctorId)
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
}
