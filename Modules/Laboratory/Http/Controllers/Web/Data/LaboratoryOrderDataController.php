<?php

namespace Modules\Laboratory\Http\Controllers\Web\Data;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Laboratory\Enums\LaboratoryOrderStatus;
use Modules\Laboratory\Http\Requests\Web\QuoteLaboratoryOrderRequest;
use Modules\Laboratory\Http\Requests\Web\TransitionLaboratoryOrderRequest;
use Modules\Laboratory\Models\Laboratory;
use Modules\Laboratory\Services\Web\LaboratoryOrderWebService;

class LaboratoryOrderDataController extends Controller
{
    use ApiResponse;

    public function __construct(private LaboratoryOrderWebService $orderService) {}

    protected function resolveLaboratory(): Laboratory
    {
        return Laboratory::where('user_id', auth('web')->id())->firstOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $laboratory = $this->resolveLaboratory();
        $orders = $this->orderService->listOrders($laboratory->id, $request->only(['status', 'search', 'limit']));

        return $this->success([
            'orders' => $orders,
            'counts' => $this->orderService->getStatusCounts($laboratory->id),
        ]);
    }

    public function show(string $orderId): JsonResponse
    {
        $laboratory = $this->resolveLaboratory();

        return $this->success(
            $this->orderService->getOrder($laboratory->id, (int) $orderId)
        );
    }

    public function review(string $orderId): JsonResponse
    {
        try {
            $laboratory = $this->resolveLaboratory();
            $order = $this->orderService->startReview($laboratory->id, (int) $orderId);

            return $this->success(
                $this->orderService->formatOrderDetail($order),
                'تم بدء مراجعة الطلب'
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 'INVALID_TRANSITION', 422);
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء تحديث الطلب');
        }
    }

    public function quote(string $orderId, QuoteLaboratoryOrderRequest $request): JsonResponse
    {
        try {
            $laboratory = $this->resolveLaboratory();
            $order = $this->orderService->quote($laboratory->id, (int) $orderId, $request->validated());

            return $this->success(
                $this->orderService->formatOrderDetail($order),
                'تم عرض السعر للمريض'
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 'VALIDATION_ERROR', 422);
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء عرض السعر');
        }
    }

    public function transition(string $orderId, TransitionLaboratoryOrderRequest $request): JsonResponse
    {
        try {
            $laboratory = $this->resolveLaboratory();
            $target = LaboratoryOrderStatus::from($request->status);
            $order = $this->orderService->transitionTo(
                $laboratory->id,
                (int) $orderId,
                $target,
                $request->only(['cancel_reason', 'scheduled_at', 'lab_notes'])
            );

            return $this->success(
                $this->orderService->formatOrderDetail($order),
                'تم تحديث حالة الطلب'
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 'INVALID_TRANSITION', 422);
        } catch (\ValueError $e) {
            return $this->error('حالة الطلب غير صالحة', 'VALIDATION_ERROR', 422);
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء تحديث حالة الطلب');
        }
    }
}
