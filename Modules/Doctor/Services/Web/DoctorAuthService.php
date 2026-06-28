<?php

namespace Modules\Doctor\Services\Web;

use App\Support\PhoneNormalizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use App\Notifications\DoctorDocumentsResubmitted;
use App\Notifications\NewDoctorRegistered;
use App\Services\AdminNotificationService;
use Modules\Auth\Models\User;
use Modules\Auth\Models\Otp;
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
            $email = !empty($data['email']) ? $data['email'] : null;
            $phone = PhoneNormalizer::toE164($data['phone']);

            $userData = [
                'name' => $data['name'],
                'phone' => $phone,
                'email' => $email,
                'password' => $data['password'],
                'role' => 'doctor',
                'status' => 'active',
                'phone_verified_at' => null,
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
                'phone' => $phone,
                'is_primary' => true,
                'is_active' => true,
            ]);

            AdminNotificationService::notify(new NewDoctorRegistered($doctor));

            return $user;
        });
    }

    public function needsPhoneVerification(User $user): bool
    {
        return $user->isDoctor() && !$user->phone_verified_at;
    }

    /**
     * @return array{otp: Otp, expose_code: bool}
     */
    public function sendPhoneVerificationOtp(User $user): array
    {
        $otp = $this->authService->sendOtp($user->phone, 'phone_verify');

        return [
            'otp' => $otp,
            'expose_code' => $this->authService->shouldExposeOtpCode(),
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    public function verifyPhoneWithOtp(User $user, string $code): void
    {
        $otp = $this->authService->verifyOtp($user->phone, $code, 'phone_verify');

        if (!$otp) {
            throw new InvalidArgumentException('الكود غير صحيح أو منتهي الصلاحية');
        }

        $user->refresh();

        if (!$user->phone_verified_at) {
            $user->update(['phone_verified_at' => now()]);
        }

        Otp::where('phone', $user->phone)->where('type', 'phone_verify')->delete();
    }

    public function login(string $phone, string $password): ?User
    {
        $variants = PhoneNormalizer::lookupVariants($phone);
        $user = User::query()->whereIn('phone', $variants)->first();

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

        AdminNotificationService::notify(new DoctorDocumentsResubmitted($doctor->fresh()));

        return $doctor->fresh();
    }

    public function getPostLoginRoute(User $user): string
    {
        if ($this->needsPhoneVerification($user)) {
            return 'doctor.verify-phone';
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
