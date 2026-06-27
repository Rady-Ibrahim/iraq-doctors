<?php

namespace Modules\Auth\Services\Api;

use App\Services\FirebaseTokenVerifier;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Auth\Exceptions\FirebaseAuthException;
use Modules\Auth\Models\User;

class FirebaseAuthService
{
    public function __construct(
        private FirebaseTokenVerifier $tokenVerifier,
        private AuthService $authService,
    ) {}

    /**
     * Unified patient auth: login if phone exists, register if not.
     *
     * @return array{user: User, token: string, is_new_user: bool}
     */
    public function authenticate(?string $firebaseToken, ?string $phone, ?string $name, ?string $testKey): array
    {
        $verified = $this->resolveVerifiedIdentity($firebaseToken, $phone, $testKey);

        return DB::transaction(function () use ($verified, $name) {
            $user = $this->findUserByPhone($verified['phone']);

            if ($user) {
                return $this->loginExistingUser($user, $verified['uid']);
            }

            return $this->registerNewPatient($verified['phone'], $verified['uid'], $name);
        });
    }

    /**
     * @return array{uid: ?string, phone: string}
     */
    private function resolveVerifiedIdentity(?string $firebaseToken, ?string $phone, ?string $testKey): array
    {
        if ($this->isTestModeRequest($testKey)) {
            if (!$phone) {
                throw new FirebaseAuthException('PHONE_REQUIRED', 'رقم الهاتف مطلوب في وضع الاختبار', 422);
            }

            try {
                $e164 = PhoneNormalizer::toE164($phone);
            } catch (InvalidArgumentException $e) {
                throw new FirebaseAuthException('INVALID_PHONE', $e->getMessage(), 422);
            }

            return [
                'uid' => 'test_' . md5($e164),
                'phone' => $e164,
            ];
        }

        if (!$firebaseToken) {
            throw new FirebaseAuthException('FIREBASE_TOKEN_REQUIRED', 'رمز Firebase مطلوب', 422);
        }

        try {
            return $this->tokenVerifier->verify($firebaseToken);
        } catch (InvalidArgumentException $e) {
            throw new FirebaseAuthException('INVALID_FIREBASE_TOKEN', $e->getMessage(), 401);
        }
    }

    private function isTestModeRequest(?string $testKey): bool
    {
        if (!config('firebase.auth_test_mode')) {
            return false;
        }

        if (app()->environment('production')) {
            return false;
        }

        $expected = (string) config('firebase.auth_test_key');

        return $expected !== '' && hash_equals($expected, (string) $testKey);
    }

    private function findUserByPhone(string $e164): ?User
    {
        $variants = PhoneNormalizer::lookupVariants($e164);

        return User::query()->whereIn('phone', $variants)->first();
    }

    /**
     * @return array{user: User, token: string, is_new_user: bool}
     */
    private function loginExistingUser(User $user, ?string $firebaseUid): array
    {
        if (!$user->isPatient()) {
            throw new FirebaseAuthException(
                'WRONG_APP',
                'هذا الرقم مسجل كحساب غير مخصص لتطبيق المريض',
                403
            );
        }

        if (!$user->isActive()) {
            throw new FirebaseAuthException('BLOCKED', 'تم حظر هذا الحساب', 403);
        }

        $updates = [
            'phone' => PhoneNormalizer::toE164($user->phone),
            'phone_verified_at' => now(),
        ];

        if ($firebaseUid) {
            $updates['firebase_uid'] = $firebaseUid;
        }

        $user->update($updates);

        return [
            'user' => $user->fresh(),
            'token' => $this->authService->createToken($user),
            'is_new_user' => false,
        ];
    }

    /**
     * @return array{user: User, token: string, is_new_user: bool}
     */
    private function registerNewPatient(string $e164, ?string $firebaseUid, ?string $name): array
    {
        if (!$name || trim($name) === '') {
            throw new FirebaseAuthException('NAME_REQUIRED', 'الاسم مطلوب لتسجيل حساب جديد', 422);
        }

        $user = User::create([
            'name' => trim($name),
            'phone' => $e164,
            'email' => null,
            'password' => Hash::make(Str::random(32)),
            'role' => 'patient',
            'status' => 'active',
            'phone_verified_at' => now(),
            'firebase_uid' => $firebaseUid,
        ]);

        return [
            'user' => $user,
            'token' => $this->authService->createToken($user),
            'is_new_user' => true,
        ];
    }

    public static function formatUserPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'role' => $user->role,
        ];
    }
}
