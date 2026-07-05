<?php

namespace Modules\Laboratory\Http\Controllers\Web\Data;

use App\Models\AppSetting;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Laboratory\Http\Requests\Web\SubscribeLaboratoryRequest;
use Modules\Laboratory\Models\Laboratory;
use Modules\Laboratory\Services\Web\LaboratorySubscriptionService;
use Modules\Subscription\Models\Subscription;

class LaboratorySubscriptionDataController extends Controller
{
    use ApiResponse;

    public function __construct(private LaboratorySubscriptionService $subscriptionService) {}

    protected function resolveLaboratory(): Laboratory
    {
        return Laboratory::where('user_id', auth('web')->id())->firstOrFail();
    }

    public function status(): JsonResponse
    {
        $laboratory = $this->resolveLaboratory();
        $status = $this->subscriptionService->getSubscriptionStatus($laboratory->id);

        return $this->success($status);
    }

    public function plans(): JsonResponse
    {
        $plans = Subscription::active()
            ->ofType('laboratory')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'description_ar' => $plan->description_ar,
                'price' => $plan->price,
                'duration_days' => $plan->duration_days,
                'is_featured' => $plan->is_featured,
                'features' => $plan->features,
            ]);

        return $this->success($plans);
    }

    public function paymentSettings(): JsonResponse
    {
        return $this->success(AppSetting::getPaymentSettings());
    }

    public function subscribe(SubscribeLaboratoryRequest $request): JsonResponse
    {
        try {
            $laboratory = $this->resolveLaboratory();
            $subscription = $this->subscriptionService->submitPaymentRequest(
                $laboratory->id,
                (int) $request->subscription_id,
                (float) $request->submitted_amount,
                $request->file('payment_receipt'),
                $request->payment_method
            );

            return $this->created([
                'id' => $subscription->id,
                'status' => $subscription->status,
            ], 'تم إرسال طلب الاشتراك. سيتم مراجعته من قبل الإدارة.');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 'VALIDATION_ERROR', 422);
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء إرسال طلب الاشتراك');
        }
    }
}
