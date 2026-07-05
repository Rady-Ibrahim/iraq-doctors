<?php

namespace Modules\Laboratory\Http\Controllers\Web\Data;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Laboratory\Http\Requests\Web\StoreLaboratoryOrderResultRequest;
use Modules\Laboratory\Models\Laboratory;
use Modules\Laboratory\Services\Web\LaboratoryOrderWebService;
use Modules\Laboratory\Services\Web\LaboratoryResultWebService;

class LaboratoryOrderResultDataController extends Controller
{
    use ApiResponse;

    public function __construct(
        private LaboratoryResultWebService $resultService,
        private LaboratoryOrderWebService $orderService,
    ) {}

    protected function resolveLaboratory(): Laboratory
    {
        return Laboratory::where('user_id', auth('web')->id())->firstOrFail();
    }

    public function index(string $orderId): JsonResponse
    {
        $laboratory = $this->resolveLaboratory();

        return $this->success(
            $this->resultService->listResults($laboratory->id, (int) $orderId)
        );
    }

    public function store(string $orderId, StoreLaboratoryOrderResultRequest $request): JsonResponse
    {
        try {
            $laboratory = $this->resolveLaboratory();
            $result = $this->resultService->uploadResult(
                $laboratory->id,
                (int) $orderId,
                $request->file('file'),
                auth('web')->id(),
                $request->notes
            );

            return $this->created(
                $this->resultService->formatResult($result),
                'تم رفع النتيجة وإشعار المريض'
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 'VALIDATION_ERROR', 422);
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء رفع النتيجة');
        }
    }

    public function destroy(string $orderId, string $resultId): JsonResponse
    {
        try {
            $laboratory = $this->resolveLaboratory();
            $this->resultService->deleteResult($laboratory->id, (int) $orderId, (int) $resultId);

            return $this->success(
                $this->orderService->formatOrderDetail(
                    $this->orderService->findOrderForLaboratory($laboratory->id, (int) $orderId)
                ),
                'تم حذف الملف'
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 'VALIDATION_ERROR', 422);
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء حذف النتيجة');
        }
    }
}
