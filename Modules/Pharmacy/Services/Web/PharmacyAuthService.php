<?php

namespace Modules\Pharmacy\Services\Web;

use App\Notifications\NewPharmacyRegistered;
use App\Notifications\PharmacyDocumentsResubmitted;
use App\Services\AdminNotificationService;
use App\Services\FirebaseTokenVerifier;
use App\Support\PhoneNormalizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Modules\Auth\Models\User;
use Modules\Pharmacy\Models\Pharmacy;

class PharmacyAuthService
{
    public function __construct(private FirebaseTokenVerifier $tokenVerifier) {}

    public function register(
        array $data,
        ?UploadedFile $logo = null,
        ?UploadedFile $commercialRegister = null,
        ?UploadedFile $licenseDocument = null,
        ?UploadedFile $ownerIdDocument = null
    ): User {
        return DB::transaction(function () use ($data, $logo, $commercialRegister, $licenseDocument, $ownerIdDocument) {
            $email = ! empty($data['email']) ? $data['email'] : null;
            $phone = PhoneNormalizer::toE164($data['phone']);

            $user = User::create([
                'name' => $data['name'],
                'phone' => $phone,
                'email' => $email,
                'password' => $data['password'],
                'role' => 'pharmacy',
                'status' => 'active',
                'phone_verified_at' => null,
            ]);

            $pharmacyData = [
                'user_id' => $user->id,
                'name' => $data['pharmacy_name'],
                'governorate_id' => $data['governorate_id'],
                'district' => $data['area'],
                'address' => $data['address'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'description_ar' => $data['description_ar'] ?? null,
                'status' => 'pending',
            ];

            if ($logo) {
                $pharmacyData['logo'] = $logo->store('pharmacies/logos', 'public');
            }

            if ($commercialRegister) {
                $pharmacyData['commercial_register_document'] = $commercialRegister->store('pharmacies/documents', 'public');
            }

            if ($licenseDocument) {
                $pharmacyData['license_document'] = $licenseDocument->store('pharmacies/documents', 'public');
            }

            if ($ownerIdDocument) {
                $pharmacyData['owner_id_document'] = $ownerIdDocument->store('pharmacies/documents', 'public');
            }

            $pharmacy = Pharmacy::create($pharmacyData);

            AdminNotificationService::notify(new NewPharmacyRegistered($pharmacy));

            return $user;
        });
    }

    public function needsPhoneVerification(User $user): bool
    {
        return $user->isPharmacy() && ! $user->phone_verified_at;
    }

    public function isFirebaseWebConfigured(): bool
    {
        return (bool) config('firebase.web_api_key')
            && (bool) config('firebase.auth_domain')
            && (bool) config('firebase.project_id');
    }

    public function isFirebaseServerConfigured(): bool
    {
        $credentials = config('firebase.credentials');

        return $credentials && is_file(base_path((string) $credentials));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function verifyPhoneWithFirebaseToken(User $user, string $firebaseToken): void
    {
        $verified = $this->tokenVerifier->verifyAndMatchPhone($firebaseToken, $user->phone);

        $user->update([
            'phone' => $verified['phone'],
            'phone_verified_at' => now(),
            'firebase_uid' => $verified['uid'],
        ]);
    }

    public function login(string $phone, string $password): ?User
    {
        $variants = PhoneNormalizer::lookupVariants($phone);
        $user = User::query()->whereIn('phone', $variants)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        if (! $user->isPharmacy() || ! $user->isActive()) {
            return null;
        }

        return $user;
    }

    public function resubmitDocuments(
        int $userId,
        ?UploadedFile $commercialRegister = null,
        ?UploadedFile $licenseDocument = null,
        ?UploadedFile $ownerIdDocument = null,
        ?UploadedFile $logo = null
    ): Pharmacy {
        $pharmacy = Pharmacy::where('user_id', $userId)->where('status', 'rejected')->firstOrFail();

        $updates = [
            'status' => 'pending',
            'reject_reason' => null,
        ];

        if ($logo) {
            $updates['logo'] = $logo->store('pharmacies/logos', 'public');
        }

        if ($commercialRegister) {
            $updates['commercial_register_document'] = $commercialRegister->store('pharmacies/documents', 'public');
        }

        if ($licenseDocument) {
            $updates['license_document'] = $licenseDocument->store('pharmacies/documents', 'public');
        }

        if ($ownerIdDocument) {
            $updates['owner_id_document'] = $ownerIdDocument->store('pharmacies/documents', 'public');
        }

        $pharmacy->update($updates);

        AdminNotificationService::notify(new PharmacyDocumentsResubmitted($pharmacy->fresh()));

        return $pharmacy->fresh();
    }

    public function getPostLoginRoute(User $user): string
    {
        if ($this->needsPhoneVerification($user)) {
            return 'pharmacy.verify-phone';
        }

        $pharmacy = Pharmacy::where('user_id', $user->id)->first();

        if (! $pharmacy) {
            return 'pharmacy.dashboard';
        }

        return match ($pharmacy->status) {
            'approved' => 'pharmacy.dashboard',
            'rejected' => 'pharmacy.rejected',
            'suspended' => 'pharmacy.suspended',
            default => 'pharmacy.pending',
        };
    }
}
