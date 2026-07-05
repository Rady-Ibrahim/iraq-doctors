<?php

namespace Modules\Pharmacy\Http\Controllers\Web\Data;

use App\Models\AppSetting;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pharmacy\Http\Requests\Web\SubscribePharmacyRequest;
use Modules\Pharmacy\Models\Pharmacy;
use Modules\Pharmacy\Services\Web\PharmacySubscriptionService;
use Modules\Subscription\Models\Subscription;

class PharmacySubscriptionDataController extends Controller
{
    use ApiResponse;

    public function __construct(private PharmacySubscriptionService $subscriptionService) {}

    protected function resolvePharmacy(): Pharmacy
    {
        return Pharmacy::where('user_id', auth('web')->id())->firstOrFail();
    }

    public function status(): JsonResponse
    {
        $pharmacy = $this->resolvePharmacy();
        $status = $this->subscriptionService->getSubscriptionStatus($pharmacy->id);

        return $this->success($status);
    }

    public function plans(): JsonResponse
    {
        $plans = Subscription::active()
            ->ofType('pharmacy')
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

    public function subscribe(SubscribePharmacyRequest $request): JsonResponse
    {
        try {
            $pharmacy = $this->resolvePharmacy();
            $subscription = $this->subscriptionService->submitPaymentRequest(
                $pharmacy->id,
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
