<?php

namespace Modules\Doctor\Services\Web;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;
use Modules\Auth\Services\Api\AuthService;
use Modules\Doctor\Models\Doctor;
use Modules\Doctor\Models\DoctorBranch;
use Modules\Doctor\Models\Governorate;

class DoctorAuthService
{
    public function __construct(private AuthService $authService) {}

    public function register(
        array $data,
        ?UploadedFile $licenseDocument = null,
        ?UploadedFile $clinicImage = null,
        ?UploadedFile $avatar = null
    ): User {
        return DB::transaction(function () use ($data, $licenseDocument, $clinicImage, $avatar) {
            $userData = [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'doctor',
                'status' => 'active',
            ];

            if ($avatar) {
                $userData['avatar'] = $avatar->store('doctors/avatars', 'public');
            }

            $user = User::create($userData);

            $doctorData = [
                'user_id' => $user->id,
                'speciality_id' => $data['speciality_id'],
                'bio_ar' => $data['bio_ar'] ?? null,
                'experience_years' => $data['experience_years'] ?? 0,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'address' => $data['address'],
                'status' => 'pending',
            ];

            if ($licenseDocument) {
                $doctorData['license_document'] = $licenseDocument->store('doctors/licenses', 'public');
            }

            if ($clinicImage) {
                $doctorData['clinic_image'] = $clinicImage->store('doctors/clinic', 'public');
            }

            $doctor = Doctor::create($doctorData);

            $governorate = Governorate::findOrFail($data['governorate_id']);

            DoctorBranch::create([
                'doctor_id' => $doctor->id,
                'branch_name' => 'العيادة الرئيسية',
                'governorate_id' => $governorate->id,
                'governorate' => $governorate->name_ar,
                'district' => $data['area'],
                'address' => $data['address'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'phone' => $data['phone'],
                'is_primary' => true,
                'is_active' => true,
            ]);

            return $user;
        });
    }

    public function sendVerificationOtp(User $user): void
    {
        $this->authService->sendOtp(null, 'register', $user->email);
    }

    public function verifyEmail(User $user, string $code): bool
    {
        $otp = $this->authService->verifyOtp(null, $code, 'register', $user->email);

        if (!$otp) {
            return false;
        }

        $user->update(['email_verified_at' => now()]);
        $otp->delete();

        return true;
    }

    public function login(string $phone, string $password): ?User
    {
        $user = User::where('phone', $phone)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        if (!$user->isDoctor() || !$user->isActive()) {
            return null;
        }

        return $user;
    }

    public function resubmitDocuments(int $userId, ?UploadedFile $licenseDocument = null, ?UploadedFile $clinicImage = null): Doctor
    {
        $doctor = Doctor::where('user_id', $userId)->where('status', 'rejected')->firstOrFail();

        $updates = [
            'status' => 'pending',
            'reject_reason' => null,
        ];

        if ($licenseDocument) {
            $updates['license_document'] = $licenseDocument->store('doctors/licenses', 'public');
        }

        if ($clinicImage) {
            $updates['clinic_image'] = $clinicImage->store('doctors/clinic', 'public');
        }

        $doctor->update($updates);

        return $doctor->fresh();
    }

    public function getPostLoginRoute(User $user): string
    {
        if (!$user->email_verified_at) {
            return 'doctor.verify-email';
        }

        $doctor = Doctor::where('user_id', $user->id)->first();

        if (!$doctor) {
            return 'doctor.dashboard';
        }

        return match ($doctor->status) {
            'approved' => 'doctor.dashboard',
            'rejected' => 'doctor.rejected',
            'suspended' => 'doctor.suspended',
            default => 'doctor.pending',
        };
    }
}
