<?php

namespace Modules\Auth\Services\Api;

use Modules\Auth\Models\User;
use Modules\Auth\Models\Otp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthService
{
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => $data['role'] ?? 'patient',
                'status' => 'active',
            ]);

            return $user;
        });
    }

    public function login(string $phone, string $password): ?User
    {
        $user = User::where('phone', $phone)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        if (!$user->isActive()) {
            return null;
        }

        return $user;
    }

    public function sendOtp(string $phone, string $type = 'login'): Otp
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Otp::where('phone', $phone)->where('type', $type)->delete();

        $otp = Otp::create([
            'phone' => $phone,
            'code' => $code,
            'type' => $type,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        return $otp;
    }

    public function verifyOtp(string $phone, string $code, string $type = 'login'): ?Otp
    {
        $otp = Otp::where('phone', $phone)
            ->where('type', $type)
            ->latest()
            ->first();

        if (!$otp) {
            return null;
        }

        if ($otp->isExpired()) {
            return null;
        }

        if ($otp->isMaxAttemptsExceeded()) {
            return null;
        }

        if ($otp->code !== $code) {
            $otp->increment('attempts');
            return null;
        }

        return $otp;
    }

    public function loginWithOtp(string $phone, string $code): ?User
    {
        $otp = $this->verifyOtp($phone, $code, 'login');

        if (!$otp) {
            return null;
        }

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return null;
        }

        $otp->delete();

        return $user;
    }

    public function resetPassword(string $phone, string $code, string $newPassword): ?User
    {
        return DB::transaction(function () use ($phone, $code, $newPassword) {
            $otp = $this->verifyOtp($phone, $code, 'password_reset');

            if (!$otp) {
                return null;
            }

            $user = User::where('phone', $phone)->first();

            if (!$user) {
                return null;
            }

            $user->update(['password' => Hash::make($newPassword)]);

            $otp->delete();

            return $user;
        });
    }

    public function updateProfile(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->update([
                'name' => $data['name'] ?? $user->name,
                'email' => $data['email'] ?? $user->email,
                'phone' => $data['phone'] ?? $user->phone,
            ]);

            if ($user->isDoctor()) {
                $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $user->id)->first();
                if ($doctor) {
                    $doctor->update([
                        'bio_ar' => $data['bio'] ?? $doctor->bio_ar,
                        'experience_years' => $data['experience_years'] ?? $doctor->experience_years,
                    ]);
                }
            }

            return $user->fresh();
        });
    }

    public function updatePassword(User $user, string $newPassword): User
    {
        $user->update(['password' => Hash::make($newPassword)]);
        return $user->fresh();
    }

    public function forgotPassword(string $phone): bool
    {
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return false;
        }

        $this->sendOtp($phone, 'password_reset');

        return true;
    }

    public function createToken(User $user): string
    {
        return $user->createToken('auth_token')->plainTextToken;
    }

    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    public function getDoctorStats(User $user): array
    {
        if (!$user->isDoctor()) {
            return [];
        }

        $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $user->id)->first();

        if (!$doctor) {
            return [];
        }

        $totalPatientsCount = \Modules\Appointment\Models\Appointment::where('doctor_id', $doctor->id)
            ->distinct('patient_id')
            ->count();

        $sentPrescriptionsCount = \Modules\MedicalRecord\Models\MedicalRecord::whereHas('appointment', function ($query) use ($doctor) {
            $query->where('doctor_id', $doctor->id);
        })->where('record_type', 'prescription')->count();

        $todayAppointmentsCount = \Modules\Appointment\Models\Appointment::where('doctor_id', $doctor->id)
            ->where('appointment_date', now()->toDateString())
            ->count();

        return [
            'total_patients_count' => $totalPatientsCount,
            'sent_prescriptions_count' => $sentPrescriptionsCount,
            'today_appointments_count' => $todayAppointmentsCount,
        ];
    }

    public function createGhostPatient(User $doctorUser, array $data): User
    {
        return DB::transaction(function () use ($doctorUser, $data) {
            $patient = User::create([
                'id' => (string) Str::uuid(),
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'] ?? Hash::make(Str::random(16)),
                'role' => 'patient',
                'status' => 'active',
                'is_ghost' => true,
                'created_by_doctor_id' => $doctorUser->id,
            ]);

            return $patient;
        });
    }
}
