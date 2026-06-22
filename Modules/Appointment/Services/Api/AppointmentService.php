<?php

namespace Modules\Appointment\Services\Api;

use App\Notifications\NewAppointmentBooked;
use Modules\Appointment\Models\Appointment;
use Modules\Doctor\Models\Doctor;
use Modules\Auth\Models\User;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    public function book(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $doctor = Doctor::findOrFail($data['doctor_id']);
            
            $appointment = Appointment::create([
                'doctor_id' => $data['doctor_id'],
                'patient_id' => $data['patient_id'],
                'doctor_schedule_id' => $data['schedule_id'] ?? null,
                'appointment_date' => $data['appointment_date'],
                'appointment_time' => $data['appointment_time'],
                'status' => 'pending',
                'price' => $doctor->consultation_fee,
                'payment_status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            $doctor->loadMissing('user');
            if ($doctor->user) {
                $doctor->user->notify(new NewAppointmentBooked($appointment));
            }

            return $appointment;
        });
    }

    public function confirm(string $appointmentId): ?Appointment
    {
        return DB::transaction(function () use ($appointmentId) {
            $appointment = Appointment::findOrFail($appointmentId);

            if (!$appointment->canBeConfirmed()) {
                return null;
            }

            $appointment->update(['status' => 'confirmed']);

            return $appointment;
        });
    }

    public function cancel(string $appointmentId, string $userId): ?Appointment
    {
        return DB::transaction(function () use ($appointmentId, $userId) {
            $appointment = Appointment::findOrFail($appointmentId);

            if (!$appointment->canBeCancelled()) {
                return null;
            }

            if ($appointment->patient_id !== $userId) {
                return null;
            }

            $appointment->update(['status' => 'cancelled']);

            return $appointment;
        });
    }

    public function complete(string $appointmentId): ?Appointment
    {
        return DB::transaction(function () use ($appointmentId) {
            $appointment = Appointment::findOrFail($appointmentId);

            if (!$appointment->canBeCompleted()) {
                return null;
            }

            $appointment->update(['status' => 'completed']);

            return $appointment;
        });
    }

    public function confirmByDoctor(int $appointmentId, int $doctorId): ?Appointment
    {
        return DB::transaction(function () use ($appointmentId, $doctorId) {
            $appointment = Appointment::where('id', $appointmentId)
                ->where('doctor_id', $doctorId)
                ->first();

            if (!$appointment || !$appointment->canBeConfirmed()) {
                return null;
            }

            $appointment->update(['status' => 'confirmed']);

            return $appointment->fresh(['patient']);
        });
    }

    public function rejectByDoctor(int $appointmentId, int $doctorId): ?Appointment
    {
        return DB::transaction(function () use ($appointmentId, $doctorId) {
            $appointment = Appointment::where('id', $appointmentId)
                ->where('doctor_id', $doctorId)
                ->first();

            if (!$appointment || $appointment->status !== 'pending') {
                return null;
            }

            $appointment->update(['status' => 'cancelled']);

            return $appointment->fresh(['patient']);
        });
    }

    public function completeByDoctor(int $appointmentId, int $doctorId): ?Appointment
    {
        return DB::transaction(function () use ($appointmentId, $doctorId) {
            $appointment = Appointment::where('id', $appointmentId)
                ->where('doctor_id', $doctorId)
                ->first();

            if (!$appointment || !$appointment->canBeCompleted()) {
                return null;
            }

            $appointment->update(['status' => 'completed']);

            return $appointment->fresh(['patient']);
        });
    }

    public function getPatientAppointments(string $patientId, string $status = null)
    {
        $query = Appointment::with(['doctor.user', 'doctor.speciality'])
            ->where('patient_id', $patientId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->get();
    }

    public function getDoctorAppointments(string $doctorId, string $status = null)
    {
        $query = Appointment::with(['patient'])
            ->where('doctor_id', $doctorId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->get();
    }

    public function reschedule(string $appointmentId, int $patientId, array $data): ?Appointment
    {
        return DB::transaction(function () use ($appointmentId, $patientId, $data) {
            $appointment = Appointment::where('id', $appointmentId)
                ->where('patient_id', $patientId)
                ->first();

            if (!$appointment) {
                return null;
            }

            // Only pending appointments can be rescheduled
            if ($appointment->status !== 'pending') {
                return null;
            }

            // Check new slot availability (exclude current appointment)
            $conflict = Appointment::where('doctor_id', $appointment->doctor_id)
                ->where('appointment_date', $data['appointment_date'])
                ->where('appointment_time', $data['appointment_time'])
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('id', '!=', $appointmentId)
                ->exists();

            if ($conflict) {
                return null;
            }

            $appointment->update([
                'appointment_date'   => $data['appointment_date'],
                'appointment_time'   => $data['appointment_time'],
                'doctor_schedule_id' => $data['schedule_id'],
            ]);

            return $appointment->fresh();
        });
    }

    public function checkAvailability(string $doctorId, string $date, string $time): bool
    {
        $existing = Appointment::where('doctor_id', $doctorId)
            ->where('appointment_date', $date)
            ->where('appointment_time', $time)
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        return !$existing;
    }
}
