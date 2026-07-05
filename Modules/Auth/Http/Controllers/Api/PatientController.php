<?php

namespace Modules\Auth\Http\Controllers\Api;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Auth\Services\Api\PatientNotificationService;
use Modules\Auth\Services\Api\PatientOrdersService;

class PatientController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PatientOrdersService $ordersService,
        private PatientNotificationService $notificationService
    ) {}

    protected function ensurePatient(): ?JsonResponse
    {
        $user = auth('sanctum')->user();

        if (! $user?->isPatient()) {
            return $this->forbidden('هذا القسم متاح للمرضى فقط', 'NOT_PATIENT');
        }

        return null;
    }

    public function orders(Request $request): JsonResponse
    {
        if ($response = $this->ensurePatient()) {
            return $response;
        }

        $historyOnly = $request->boolean('history');
        $orders = $this->ordersService->getUnifiedOrders(auth('sanctum')->id(), $historyOnly);

        return $this->success($orders);
    }

    public function notifications(Request $request): JsonResponse
    {
        if ($response = $this->ensurePatient()) {
            return $response;
        }

        $unreadOnly = $request->boolean('unread');
        $limit = min((int) $request->input('limit', 30), 100);

        return $this->success(
            $this->notificationService->list(auth('sanctum')->user(), $unreadOnly, $limit)
        );
    }

    public function markNotificationRead(string $id): JsonResponse
    {
        if ($response = $this->ensurePatient()) {
            return $response;
        }

        try {
            $this->notificationService->markRead(auth('sanctum')->user(), $id);

            return $this->success(null, 'تم تعليم الإشعار كمقروء');
        } catch (\Exception $e) {
            return $this->notFound('الإشعار غير موجود');
        }
    }

    public function markAllNotificationsRead(): JsonResponse
    {
        if ($response = $this->ensurePatient()) {
            return $response;
        }

        $count = $this->notificationService->markAllRead(auth('sanctum')->user());

        return $this->success(['marked_count' => $count], 'تم تعليم جميع الإشعارات كمقروءة');
    }
}
