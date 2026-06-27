<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Models\User;

class OneSignalService
{
    public static function isEnabled(): bool
    {
        return (bool) config('onesignal.enabled')
            && config('onesignal.app_id')
            && config('onesignal.rest_api_key');
    }

    /**
     * @param  array<int, string>  $playerIds
     * @param  array<string, mixed>  $data
     */
    public function send(array $playerIds, string $title, string $message, array $data = []): bool
    {
        if (!self::isEnabled() || empty($playerIds)) {
            return false;
        }

        $payload = [
            'app_id' => config('onesignal.app_id'),
            'include_subscription_ids' => array_values($playerIds),
            'headings' => ['en' => $title, 'ar' => $title],
            'contents' => ['en' => $message, 'ar' => $message],
            'data' => $data,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Key ' . config('onesignal.rest_api_key'),
                'Accept' => 'application/json',
            ])->post(config('onesignal.api_url'), $payload);

            if (!$response->successful()) {
                Log::warning('OneSignal push failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('OneSignal push error: ' . $e->getMessage());

            return false;
        }
    }

    public function sendToUser(User $user, string $title, string $message, array $data = []): bool
    {
        if (!$user->isPatient()) {
            return false;
        }

        $playerIds = DeviceToken::query()
            ->where('user_id', $user->id)
            ->pluck('player_id')
            ->all();

        return $this->send($playerIds, $title, $message, $data);
    }
}
