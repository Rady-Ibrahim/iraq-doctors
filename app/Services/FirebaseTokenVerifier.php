<?php

namespace App\Services;

use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

class FirebaseTokenVerifier
{
    public function __construct(private ?FirebaseAuth $firebaseAuth = null) {}

    /**
     * @return array{uid: string, phone: string}
     *
     * @throws InvalidArgumentException
     */
    public function verify(string $idToken): array
    {
        if (!$this->firebaseAuth) {
            throw new InvalidArgumentException('Firebase غير مُعد على السيرفر');
        }

        try {
            $verified = $this->firebaseAuth->verifyIdToken($idToken);
        } catch (FailedToVerifyToken $e) {
            Log::warning('Firebase token verification failed', ['message' => $e->getMessage()]);
            throw new InvalidArgumentException('رمز Firebase غير صالح أو منتهي الصلاحية');
        }

        $claims = $verified->claims();
        $phone = $claims->get('phone_number');

        if (!$phone) {
            throw new InvalidArgumentException('رمز Firebase لا يحتوي على رقم هاتف');
        }

        return [
            'uid' => (string) $claims->get('sub'),
            'phone' => PhoneNormalizer::toE164((string) $phone),
        ];
    }

    /**
     * Verify ID token and ensure Firebase phone matches the expected account phone.
     *
     * @return array{uid: string, phone: string}
     *
     * @throws InvalidArgumentException
     */
    public function verifyAndMatchPhone(string $idToken, string $expectedPhone): array
    {
        $verified = $this->verify($idToken);

        try {
            $expectedE164 = PhoneNormalizer::toE164($expectedPhone);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException('رقم الهاتف المسجل في الحساب غير صحيح');
        }

        if ($verified['phone'] !== $expectedE164) {
            throw new InvalidArgumentException('رقم الهاتف في رمز التحقق لا يطابق رقم حسابك');
        }

        return $verified;
    }
}
