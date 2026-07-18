<?php

namespace Modules\Pharmacy\Http\Controllers\Web\Data;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Pharmacy\Enums\PharmacyOrderStatus;
use Modules\Pharmacy\Http\Requests\Web\QuotePharmacyOrderRequest;
use Modules\Pharmacy\Http\Requests\Web\TransitionPharmacyOrderRequest;
use Modules\Pharmacy\Models\Pharmacy;
use Modules\Pharmacy\Services\Web\PharmacyOrderWebService;

class PharmacyOrderDataController extends Controller
{
    use ApiResponse;

    public function __construct(private PharmacyOrderWebService $orderService) {}

    protected function resolvePharmacy(): Pharmacy
    {
        return Pharmacy::where('user_id', auth('web')->id())->firstOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $pharmacy = $this->resolvePharmacy();
        $orders = $this->orderService->listOrders($pharmacy->id, $request->only(['status', 'search', 'limit']));

        return $this->success([
            'orders' => $orders,
            'counts' => $this->orderService->getStatusCounts($pharmacy->id),
        ]);
    }

    public function show(string $orderId): JsonResponse
    {
        try {
            $pharmacy = $this->resolvePharmacy();

            return $this->success(
                $this->orderService->getOrder($pharmacy->id, (int) $orderId)
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('الطلب غير موجود');
        } catch (\Exception $e) {
            report($e);

            return $this->serverError('حدث خطأ أثناء تحميل الطلب');
        }
    }

    public function review(string $orderId): JsonResponse
    {
        try {
            $pharmacy = $this->resolvePharmacy();
            $order = $this->orderService->startReview($pharmacy->id, (int) $orderId);

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

    public function quote(string $orderId, QuotePharmacyOrderRequest $request): JsonResponse
    {
        try {
            $pharmacy = $this->resolvePharmacy();
            $order = $this->orderService->quote($pharmacy->id, (int) $orderId, $request->validated());

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

    public function transition(string $orderId, TransitionPharmacyOrderRequest $request): JsonResponse
    {
        try {
            $pharmacy = $this->resolvePharmacy();
            $target = PharmacyOrderStatus::from($request->status);
            $order = $this->orderService->transitionTo(
                $pharmacy->id,
                (int) $orderId,
                $target,
                $request->only(['cancel_reason', 'pharmacy_notes'])
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
