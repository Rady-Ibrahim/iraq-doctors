<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * OTP delivery via WasenderAPI (WhatsApp).
 * Falls back to log when api_key is empty outside production.
 */
class OtpSmsSender
{
    public function send(string $phoneE164, string $code, string $type): void
    {
        $message = $this->buildMessage($type, $code);
        $to = ltrim($phoneE164, '+');

        if (! $this->isConfigured()) {
            if (app()->environment('production') || ! config('wasender.log_fallback', true)) {
                throw new RuntimeException(
                    'WasenderAPI غير مُعد. ضع WASENDER_API_KEY في ملف .env'
                );
            }

            Log::info('OTP WhatsApp (log fallback — set WASENDER_API_KEY to send for real)', [
                'phone' => $phoneE164,
                'to' => $to,
                'type' => $type,
                'code' => $code,
                'message' => $message,
            ]);

            return;
        }

        try {
            $response = Http::baseUrl((string) config('wasender.base_url'))
                ->withToken((string) config('wasender.api_key'))
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('wasender.timeout', 15))
                ->post('/api/send-message', [
                    'to' => $to,
                    'text' => $message,
                ]);
        } catch (ConnectionException $e) {
            Log::error('Wasender OTP connection failed', [
                'phone' => $phoneE164,
                'type' => $type,
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException('تعذر الاتصال بخدمة واتساب. حاول مرة أخرى.', 0, $e);
        }

        if (! $response->successful()) {
            Log::error('Wasender OTP HTTP error', [
                'phone' => $phoneE164,
                'type' => $type,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            throw new RuntimeException('فشل إرسال كود التحقق عبر واتساب. حاول مرة أخرى.');
        }

        $body = $response->json();
        if (is_array($body) && array_key_exists('success', $body) && $body['success'] === false) {
            Log::error('Wasender OTP API rejected message', [
                'phone' => $phoneE164,
                'type' => $type,
                'body' => $body,
            ]);

            $apiMessage = $body['message'] ?? $body['error'] ?? null;

            throw new RuntimeException(
                is_string($apiMessage) && $apiMessage !== ''
                    ? $apiMessage
                    : 'فشل إرسال كود التحقق عبر واتساب. حاول مرة أخرى.'
            );
        }

        Log::info('OTP WhatsApp sent via Wasender', [
            'phone' => $phoneE164,
            'type' => $type,
        ]);
    }

    public function isConfigured(): bool
    {
        return filled(config('wasender.api_key'));
    }

    public function deliversRealSms(): bool
    {
        return $this->isConfigured();
    }

    private function buildMessage(string $type, string $code): string
    {
        $label = match ($type) {
            'password_reset' => 'إعادة تعيين كلمة المرور',
            'phone_verify' => 'تفعيل رقم الهاتف',
            'register' => 'تأكيد التسجيل',
            'login' => 'تسجيل الدخول',
            default => 'التحقق',
        };

        $minutes = (int) config('otp.expires_minutes', 10);

        return "أطباء العراق — رمز {$label}: {$code}\nصالح لمدة {$minutes} دقائق.";
    }
}
