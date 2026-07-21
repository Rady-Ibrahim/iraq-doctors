<?php

namespace Modules\Auth\Services\Api;

use App\Services\OtpSmsSender;
use App\Support\PhoneNormalizer;
use InvalidArgumentException;
use Modules\Auth\Models\User;
use Modules\Auth\Models\Otp;
use Modules\Doctor\Models\Doctor;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthService
{
    public function __construct(private OtpSmsSender $smsSender) {}

    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $phone = PhoneNormalizer::toE164($data['phone']);

            // Register only — phone verification is a separate OTP step before login.
            return User::create([
                'name' => $data['name'],
                'phone' => $phone,
                'email' => $data['email'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => 'patient',
                'status' => 'active',
                'phone_verified_at' => null,
            ]);
        });
    }

    /**
     * Login with phone + password.
     * Returns null if credentials invalid or account inactive.
     *
     * @throws InvalidArgumentException when patient phone is not verified
     */
    public function login(string $identifier, string $password): ?User
    {
        try {
            $phone = PhoneNormalizer::toE164($identifier);
        } catch (InvalidArgumentException) {
            $phone = $identifier;
        }

        $variants = PhoneNormalizer::lookupVariants($phone);
        $user = User::query()->whereIn('phone', $variants)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        if (!$user->isActive()) {
            return null;
        }

        if ($user->isPatient() && !$user->phone_verified_at) {
            throw new InvalidArgumentException('PHONE_NOT_VERIFIED');
        }

        return $user;
    }

    /**
     * Send OTP via SMS to the given phone.
     */
    public function sendOtp(string $phone, string $type = 'login'): Otp
    {
        $phone = PhoneNormalizer::toE164($phone);
        $type = $this->normalizeOtpType($type);

        if (in_array($type, ['phone_verify', 'password_reset', 'login'], true)) {
            $variants = PhoneNormalizer::lookupVariants($phone);
            $exists = User::query()->whereIn('phone', $variants)->exists();

            if (!$exists) {
                throw new InvalidArgumentException('USER_NOT_FOUND');
            }
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Otp::where('phone', $phone)->where('type', $type)->delete();

        $otp = Otp::create([
            'phone' => $phone,
            'email' => null,
            'code' => $code,
            'type' => $type,
            'attempts' => 0,
            'expires_at' => now()->addMinutes((int) config('otp.expires_minutes', 10)),
        ]);

        try {
            $this->smsSender->send($phone, $code, $type);
        } catch (\Throwable $e) {
            $otp->delete();

            throw $e;
        }

        return $otp;
    }

    public function verifyOtp(string $phone, string $code, string $type = 'login'): ?Otp
    {
        $phone = PhoneNormalizer::toE164($phone);
        $type = $this->normalizeOtpType($type);

        $otp = Otp::query()
            ->where('type', $type)
            ->where('phone', $phone)
            ->latest()
            ->first();

        if (!$otp || $otp->isExpired() || $otp->isMaxAttemptsExceeded()) {
            return null;
        }

        if ($otp->code !== $code) {
            $otp->increment('attempts');

            return null;
        }

        $otp->update(['verified_at' => now()]);

        if ($type === 'phone_verify') {
            $this->markPhoneVerifiedForUser($phone);
            $otp->delete();

            return $otp;
        }

        return $otp->fresh();
    }

    public function markPhoneVerifiedForUser(string $phoneE164): void
    {
        $variants = PhoneNormalizer::lookupVariants($phoneE164);

        User::query()
            ->whereIn('phone', $variants)
            ->whereNull('phone_verified_at')
            ->update(['phone_verified_at' => now()]);
    }

    public function loginWithOtp(string $phone, string $code): ?User
    {
        $otp = $this->verifyOtp($phone, $code, 'login');

        if (!$otp) {
            return null;
        }

        $variants = PhoneNormalizer::lookupVariants($phone);
        $user = User::query()->whereIn('phone', $variants)->first();

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

            $variants = PhoneNormalizer::lookupVariants($phone);
            $user = User::query()->whereIn('phone', $variants)->first();

            if (!$user) {
                return null;
            }

            $user->update(['password' => Hash::make($newPassword)]);

            $otp->delete();

            return $user;
        });
    }

    public function shouldExposeOtpCode(): bool
    {
        if (app()->environment('production')) {
            return false;
        }

        return (bool) config('otp.expose_code_in_response', false);
    }

    private function normalizeOtpType(string $type): string
    {
        return match ($type) {
            'reset_password' => 'password_reset',
            default => $type,
        };
    }

    public function updateProfile(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->update([
                'name' => $data['name'] ?? $user->name,
                'email' => $data['email'] ?? $user->email,
                'phone' => $data['phone'] ?? $user->phone,
                'birthdate' => $data['birthdate'] ?? $user->birthdate,
                'gender' => $data['gender'] ?? $user->gender,
                'city' => $data['city'] ?? $user->city,
                'district' => $data['district'] ?? $user->district,
                'address' => $data['address'] ?? $user->address,
            ]);

            if ($user->isDoctor()) {
                $doctor = Doctor::where('user_id', $user->id)->first();
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
        $variants = PhoneNormalizer::lookupVariants($phone);
        $user = User::query()->whereIn('phone', $variants)->first();

        if (!$user) {
            return false;
        }

        $this->sendOtp($user->phone, 'password_reset');

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

        $doctor = Doctor::where('user_id', $user->id)->first();

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

    public function createAdmin(array $data): User
    {
        return DB::transaction(function () use ($data) {
            return User::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'admin',
                'status' => 'active',
            ]);
        });
    }

    public function createDoctor(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $phone = PhoneNormalizer::toE164($data['phone']);

            $user = User::create([
                'name' => $data['name'],
                'phone' => $phone,
                'email' => $data['email'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => 'doctor',
                'status' => 'active',
                'phone_verified_at' => now(),
            ]);

            Doctor::create([
                'user_id' => $user->id,
                'speciality_id' => $data['speciality_id'] ?? null,
                'bio_ar' => $data['bio_ar'] ?? null,
                'bio_en' => $data['bio_en'] ?? null,
                'experience_years' => $data['experience_years'] ?? 0,
                'status' => 'pending',
            ]);

            return $user;
        });
    }
}
