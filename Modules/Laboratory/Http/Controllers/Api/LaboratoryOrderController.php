<?php

namespace Modules\Laboratory\Http\Controllers\Api;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Laboratory\Http\Requests\Api\CreateLaboratoryOrderRequest;
use Modules\Laboratory\Services\Api\LaboratoryOrderService;

class LaboratoryOrderController extends Controller
{
    use ApiResponse;

    public function __construct(private LaboratoryOrderService $orderService) {}

    protected function ensurePatient(): ?JsonResponse
    {
        $user = auth('sanctum')->user();

        if (! $user?->isPatient()) {
            return $this->forbidden('فقط المرضى يمكنهم إدارة طلبات التحاليل', 'NOT_PATIENT');
        }

        return null;
    }

    public function store(CreateLaboratoryOrderRequest $request): JsonResponse
    {
        if ($response = $this->ensurePatient()) {
            return $response;
        }

        try {
            $order = $this->orderService->createOrder(
                auth('sanctum')->id(),
                $request->validated(),
                $request->file('prescription_image')
            );

            return $this->created(
                $this->orderService->formatOrderForPatient($order, true),
                'تم إرسال طلب التحاليل بنجاح'
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 'VALIDATION_ERROR', 422);
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء إنشاء الطلب');
        }
    }

    public function myOrders(Request $request): JsonResponse
    {
        if ($response = $this->ensurePatient()) {
            return $response;
        }

        $historyOnly = $request->boolean('history');
        $orders = $this->orderService->getPatientOrders(
            auth('sanctum')->id(),
            $request->input('status'),
            $historyOnly
        );

        return $this->success(
            $orders->map(fn ($order) => $this->orderService->formatOrderForPatient($order))->values()->all()
        );
    }

    public function show(string $id): JsonResponse
    {
        if ($response = $this->ensurePatient()) {
            return $response;
        }

        $order = $this->orderService->getPatientOrder(auth('sanctum')->id(), (int) $id);

        if (! $order) {
            return $this->notFound('الطلب غير موجود');
        }

        return $this->success($this->orderService->formatOrderForPatient($order, true));
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        if ($response = $this->ensurePatient()) {
            return $response;
        }

        $request->validate(['reason' => 'nullable|string|max:1000']);

        try {
            $order = $this->orderService->cancelOrder(
                auth('sanctum')->id(),
                (int) $id,
                $request->input('reason')
            );

            return $this->success(
                $this->orderService->formatOrderForPatient($order, true),
                'تم إلغاء الطلب'
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 'INVALID_STATE', 422);
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء إلغاء الطلب');
        }
    }

    public function acceptQuote(string $id): JsonResponse
    {
        if ($response = $this->ensurePatient()) {
            return $response;
        }

        try {
            $order = $this->orderService->acceptQuote(auth('sanctum')->id(), (int) $id);

            return $this->success(
                $this->orderService->formatOrderForPatient($order, true),
                'تم قبول عرض السعر'
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 'INVALID_STATE', 422);
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء قبول عرض السعر');
        }
    }
}
