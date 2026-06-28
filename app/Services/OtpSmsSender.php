<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class OtpSmsSender
{
    public function send(string $phoneE164, string $code, string $type): void
    {
        $message = match ($type) {
            'password_reset' => "كود إعادة تعيين كلمة المرور: {$code}",
            'phone_verify' => "كود تفعيل رقم هاتفك: {$code}",
            'register' => "كود تفعيل حسابك: {$code}",
            default => "كود التحقق: {$code}",
        };

        // TODO: integrate SMS provider (Firebase / Twilio) for production delivery.
        Log::info('OTP SMS', [
            'phone' => $phoneE164,
            'type' => $type,
            'code' => $code,
            'message' => $message,
        ]);
    }
}
