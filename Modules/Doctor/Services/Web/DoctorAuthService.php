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
use App\Services\OtpSmsSender;
use Modules\Auth\Models\User;
use Modules\Auth\Services\Api\AuthService;
use Modules\Doctor\Models\Doctor;
use Modules\Doctor\Models\DoctorBranch;
use Modules\Doctor\Models\DoctorStaffMember;
use Modules\Doctor\Models\Governorate;

class DoctorAuthService
{
    public function __construct(
        private AuthService $authService,
        private OtpSmsSender $otpSender,
    ) {}

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

    public function isOtpDeliveryConfigured(): bool
    {
        return $this->otpSender->isConfigured()
            || (! app()->environment('production') && (bool) config('wasender.log_fallback', true));
    }

    public function sendPhoneVerificationOtp(User $user): void
    {
        $this->authService->sendOtp($user->phone, 'phone_verify');
    }

    /**
     * @throws InvalidArgumentException
     */
    public function verifyPhoneWithOtp(User $user, string $code): void
    {
        $otp = $this->authService->verifyOtp($user->phone, $code, 'phone_verify');

        if (! $otp) {
            throw new InvalidArgumentException('كود التحقق غير صحيح أو منتهي الصلاحية');
        }

        $user->update([
            'phone' => PhoneNormalizer::toE164($user->phone),
            'phone_verified_at' => now(),
        ]);
    }

    public function login(string $phone, string $password): ?User
    {
        $variants = PhoneNormalizer::lookupVariants($phone);
        $user = User::query()->whereIn('phone', $variants)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        if ($user->isDoctor()) {
            return $user->isActive() ? $user : null;
        }

        if ($user->isDoctorStaff()) {
            if (! $user->isActive()) {
                return null;
            }

            $isActiveStaff = DoctorStaffMember::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->exists();

            return $isActiveStaff ? $user : null;
        }

        return null;
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
        if ($user->isDoctorStaff()) {
            $staffMember = DoctorStaffMember::with('doctor')->where('user_id', $user->id)->first();
            $doctor = $staffMember?->doctor;

            if (! $doctor) {
                return 'doctor.login';
            }

            return $doctor->status === 'approved' ? 'doctor.dashboard' : 'doctor.pending';
        }

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
