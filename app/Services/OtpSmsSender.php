<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Patient / dev OTP delivery. Doctor SMS uses Firebase Web SDK (not this class).
 */
class OtpSmsSender
{
    public function send(string $phoneE164, string $code, string $type): void
    {
        $message = match ($type) {
            'password_reset' => "Password reset code: {$code}",
            'phone_verify' => "Phone verify code: {$code}",
            'register' => "Account verify code: {$code}",
            default => "Verification code: {$code}",
        };

        Log::info('OTP SMS (log driver — not sent to phone; use Firebase for doctor)', [
            'phone' => $phoneE164,
            'type' => $type,
            'code' => $code,
            'message' => $message,
        ]);
    }

    public function deliversRealSms(): bool
    {
        return false;
    }
}
