<?php

namespace Modules\Auth\Http\Controllers\Api;

use App\Models\DeviceToken;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Http\Requests\Api\RegisterDeviceRequest;

class DeviceController extends Controller
{
    use ApiResponse;

    /**
     * Register a OneSignal subscription id for the patient mobile app only.
     */
    public function register(RegisterDeviceRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isPatient()) {
            return $this->error(
                'تسجيل الإشعارات متاح لتطبيق المريض فقط',
                'PATIENT_ONLY',
                403
            );
        }

        DeviceToken::updateOrCreate(
            ['player_id' => $request->player_id],
            [
                'user_id' => $user->id,
                'platform' => $request->platform,
            ]
        );

        return $this->success([
            'player_id' => $request->player_id,
            'platform' => $request->platform,
        ], 'تم تسجيل الجهاز للإشعارات');
    }

    public function unregister(RegisterDeviceRequest $request): JsonResponse
    {
        if (!$request->user()->isPatient()) {
            return $this->error(
                'تسجيل الإشعارات متاح لتطبيق المريض فقط',
                'PATIENT_ONLY',
                403
            );
        }

        DeviceToken::query()
            ->where('user_id', $request->user()->id)
            ->where('player_id', $request->player_id)
            ->delete();

        return $this->success(null, 'تم إلغاء تسجيل الجهاز');
    }

    public function unregisterAll(): JsonResponse
    {
        $user = request()->user();

        if (!$user->isPatient()) {
            return $this->error(
                'تسجيل الإشعارات متاح لتطبيق المريض فقط',
                'PATIENT_ONLY',
                403
            );
        }

        DeviceToken::query()
            ->where('user_id', $user->id)
            ->delete();

        return $this->success(null, 'تم إلغاء تسجيل جميع الأجهزة');
    }
}
