<?php

namespace Modules\Laboratory\Services\Web;

use App\Notifications\LaboratoryDocumentsResubmitted;
use App\Notifications\NewLaboratoryRegistered;
use App\Services\AdminNotificationService;
use App\Services\OtpSmsSender;
use App\Support\PhoneNormalizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Modules\Auth\Models\User;
use Modules\Auth\Services\Api\AuthService;
use Modules\Laboratory\Models\Laboratory;

class LaboratoryAuthService
{
    public function __construct(
        private AuthService $authService,
        private OtpSmsSender $otpSender,
    ) {}

    public function register(
        array $data,
        ?UploadedFile $logo = null,
        ?UploadedFile $commercialRegister = null,
        ?UploadedFile $licenseDocument = null,
        ?UploadedFile $ownerIdDocument = null,
        ?UploadedFile $accreditationDocument = null
    ): User {
        return DB::transaction(function () use (
            $data,
            $logo,
            $commercialRegister,
            $licenseDocument,
            $ownerIdDocument,
            $accreditationDocument
        ) {
            $email = ! empty($data['email']) ? $data['email'] : null;
            $phone = PhoneNormalizer::toE164($data['phone']);

            $user = User::create([
                'name' => $data['name'],
                'phone' => $phone,
                'email' => $email,
                'password' => $data['password'],
                'role' => 'laboratory',
                'status' => 'active',
                'phone_verified_at' => null,
            ]);

            $laboratoryData = [
                'user_id' => $user->id,
                'name' => $data['laboratory_name'],
                'governorate_id' => $data['governorate_id'],
                'district' => $data['area'],
                'address' => $data['address'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'description_ar' => $data['description_ar'] ?? null,
                'status' => 'pending',
            ];

            if ($logo) {
                $laboratoryData['logo'] = $logo->store('laboratories/logos', 'public');
            }

            if ($commercialRegister) {
                $laboratoryData['commercial_register_document'] = $commercialRegister->store('laboratories/documents', 'public');
            }

            if ($licenseDocument) {
                $laboratoryData['license_document'] = $licenseDocument->store('laboratories/documents', 'public');
            }

            if ($ownerIdDocument) {
                $laboratoryData['owner_id_document'] = $ownerIdDocument->store('laboratories/documents', 'public');
            }

            if ($accreditationDocument) {
                $laboratoryData['accreditation_document'] = $accreditationDocument->store('laboratories/documents', 'public');
            }

            $laboratory = Laboratory::create($laboratoryData);

            AdminNotificationService::notify(new NewLaboratoryRegistered($laboratory));

            return $user;
        });
    }

    public function needsPhoneVerification(User $user): bool
    {
        return $user->isLaboratory() && ! $user->phone_verified_at;
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

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        if (! $user->isLaboratory() || ! $user->isActive()) {
            return null;
        }

        return $user;
    }

    public function resubmitDocuments(
        int $userId,
        ?UploadedFile $commercialRegister = null,
        ?UploadedFile $licenseDocument = null,
        ?UploadedFile $ownerIdDocument = null,
        ?UploadedFile $logo = null,
        ?UploadedFile $accreditationDocument = null
    ): Laboratory {
        $laboratory = Laboratory::where('user_id', $userId)->where('status', 'rejected')->firstOrFail();

        $updates = [
            'status' => 'pending',
            'reject_reason' => null,
        ];

        if ($logo) {
            $updates['logo'] = $logo->store('laboratories/logos', 'public');
        }

        if ($commercialRegister) {
            $updates['commercial_register_document'] = $commercialRegister->store('laboratories/documents', 'public');
        }

        if ($licenseDocument) {
            $updates['license_document'] = $licenseDocument->store('laboratories/documents', 'public');
        }

        if ($ownerIdDocument) {
            $updates['owner_id_document'] = $ownerIdDocument->store('laboratories/documents', 'public');
        }

        if ($accreditationDocument) {
            $updates['accreditation_document'] = $accreditationDocument->store('laboratories/documents', 'public');
        }

        $laboratory->update($updates);

        AdminNotificationService::notify(new LaboratoryDocumentsResubmitted($laboratory->fresh()));

        return $laboratory->fresh();
    }

    public function getPostLoginRoute(User $user): string
    {
        if ($this->needsPhoneVerification($user)) {
            return 'laboratory.verify-phone';
        }

        $laboratory = Laboratory::where('user_id', $user->id)->first();

        if (! $laboratory) {
            return 'laboratory.dashboard';
        }

        return match ($laboratory->status) {
            'approved' => 'laboratory.dashboard',
            'rejected' => 'laboratory.rejected',
            'suspended' => 'laboratory.suspended',
            default => 'laboratory.pending',
        };
    }
}
