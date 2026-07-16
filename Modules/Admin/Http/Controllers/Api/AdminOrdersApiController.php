<?php

namespace Modules\Admin\Http\Controllers\Api;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Services\AdminOrdersService;

class AdminOrdersApiController extends Controller
{
    use ApiResponse;

    public function __construct(private AdminOrdersService $ordersService) {}

    public function laboratoryOrders(Request $request): JsonResponse
    {
        try {
            $paginator = $this->ordersService->listLaboratoryOrders($request->all());
            $paginator->getCollection()->transform(
                fn ($order) => $this->ordersService->formatLaboratoryOrder($order)
            );

            return $this->paginated(
                $paginator->items(),
                $paginator->total(),
                $paginator->currentPage(),
                $paginator->perPage()
            );
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب طلبات المختبرات');
        }
    }

    public function laboratoryOrderDetails(int $id): JsonResponse
    {
        try {
            return $this->success($this->ordersService->getLaboratoryOrder($id));
        } catch (\Exception $e) {
            return $this->notFound('الطلب غير موجود');
        }
    }

    public function pharmacyOrders(Request $request): JsonResponse
    {
        try {
            $paginator = $this->ordersService->listPharmacyOrders($request->all());
            $paginator->getCollection()->transform(
                fn ($order) => $this->ordersService->formatPharmacyOrder($order)
            );

            return $this->paginated(
                $paginator->items(),
                $paginator->total(),
                $paginator->currentPage(),
                $paginator->perPage()
            );
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب طلبات الصيدليات');
        }
    }

    public function pharmacyOrderDetails(int $id): JsonResponse
    {
        try {
            return $this->success($this->ordersService->getPharmacyOrder($id));
        } catch (\Exception $e) {
            return $this->notFound('الطلب غير موجود');
        }
    }

    public function ordersReport(Request $request): JsonResponse
    {
        try {
            return $this->success($this->ordersService->getOrdersReport($request->all()));
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب التقرير');
        }
    }
}
