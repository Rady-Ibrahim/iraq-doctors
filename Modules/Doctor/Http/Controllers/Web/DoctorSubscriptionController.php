<?php

namespace Modules\Doctor\Http\Controllers\Web;

use App\Models\AppSetting;
use App\Traits\ApiResponse;
use App\Traits\ResolvesDoctorDashboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Subscription\Models\Subscription;
use Modules\Subscription\Services\SubscriptionService;

class DoctorSubscriptionController extends Controller
{
    use ApiResponse;
    use ResolvesDoctorDashboard;

    public function __construct(private SubscriptionService $subscriptionService) {}

    public function plans(): JsonResponse
    {
        $plans = Subscription::active()->orderBy('sort_order')->get()->map(fn ($plan) => [
            'id' => $plan->id,
            'name' => $plan->name,
            'description_ar' => $plan->description_ar,
            'price' => $plan->price,
            'duration_days' => $plan->duration_days,
            'is_featured' => $plan->is_featured,
            'has_analytics' => $plan->has_analytics,
            'has_banner' => $plan->has_banner,
            'features' => $plan->features,
        ]);

        return $this->success($plans);
    }

    public function paymentSettings(): JsonResponse
    {
        return $this->success(AppSetting::getPaymentSettings());
    }

    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'subscription_id' => 'required|integer|exists:subscriptions,id',
            'submitted_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:vodafone_cash,bank_transfer',
            'payment_receipt' => 'required|file|mimes:jpg,jpeg,png,pdf|max:' . config('uploads.max_document_kb', 10240),
        ]);

        try {
            $doctor = $this->resolveDoctor();
            $subscription = $this->subscriptionService->submitPaymentRequest(
                $doctor->id,
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
