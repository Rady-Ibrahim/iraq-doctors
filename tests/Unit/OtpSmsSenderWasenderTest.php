<?php

namespace Tests\Unit;

use App\Services\OtpSmsSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class OtpSmsSenderWasenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'wasender.base_url' => 'https://www.wasenderapi.com',
            'wasender.timeout' => 10,
            'wasender.log_fallback' => true,
            'otp.expires_minutes' => 10,
        ]);
    }

    public function test_sends_whatsapp_otp_via_wasender_when_api_key_is_set(): void
    {
        config(['wasender.api_key' => 'test-session-api-key']);

        Http::fake([
            'www.wasenderapi.com/api/send-message' => Http::response([
                'success' => true,
                'data' => ['msgId' => 'mock-1'],
            ], 200),
        ]);

        $sender = new OtpSmsSender;
        $sender->send('+9647901234567', '482915', 'phone_verify');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://www.wasenderapi.com/api/send-message'
                && $request->hasHeader('Authorization', 'Bearer test-session-api-key')
                && $request['to'] === '9647901234567'
                && str_contains((string) $request['text'], '482915')
                && str_contains((string) $request['text'], 'تفعيل رقم الهاتف');
        });

        $this->assertTrue($sender->isConfigured());
        $this->assertTrue($sender->deliversRealSms());
    }

    public function test_throws_when_wasender_returns_http_error(): void
    {
        config(['wasender.api_key' => 'test-session-api-key']);

        Http::fake([
            'www.wasenderapi.com/api/send-message' => Http::response([
                'success' => false,
                'message' => 'Session disconnected',
            ], 500),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('فشل إرسال كود التحقق عبر واتساب');

        (new OtpSmsSender)->send('+9647901234567', '111222', 'login');
    }

    public function test_throws_when_wasender_returns_success_false(): void
    {
        config(['wasender.api_key' => 'test-session-api-key']);

        Http::fake([
            'www.wasenderapi.com/api/send-message' => Http::response([
                'success' => false,
                'message' => 'Invalid phone number',
            ], 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid phone number');

        (new OtpSmsSender)->send('+201012345678', '333444', 'register');
    }

    public function test_log_fallback_when_api_key_missing_outside_production(): void
    {
        config(['wasender.api_key' => '']);

        Http::fake();
        Log::spy();

        $sender = new OtpSmsSender;
        $sender->send('+9647901234567', '999888', 'password_reset');

        Http::assertNothingSent();
        $this->assertFalse($sender->isConfigured());

        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context) {
                return str_contains($message, 'log fallback')
                    && ($context['code'] ?? null) === '999888'
                    && ($context['to'] ?? null) === '9647901234567';
            })
            ->once();
    }

    public function test_throws_in_production_when_api_key_missing(): void
    {
        config(['wasender.api_key' => '']);
        app()->detectEnvironment(fn () => 'production');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('WASENDER_API_KEY');

        (new OtpSmsSender)->send('+9647901234567', '123456', 'login');
    }
}
