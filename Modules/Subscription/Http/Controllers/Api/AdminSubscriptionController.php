<?php

namespace Modules\Subscription\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Subscription\Services\SubscriptionService;
use Modules\Subscription\Http\Requests\CreateSubscriptionRequest;
use App\Traits\ApiResponse;

class AdminSubscriptionController extends Controller
{
    use ApiResponse;

    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Get all subscriptions with stats
     */
    public function index(): JsonResponse
    {
        try {
            $subscriptions = $this->subscriptionService->getAllPlans();
            return $this->success($subscriptions, 'تم جلب الاشتراكات بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب الاشتراكات');
        }
    }

    /**
     * Get subscription statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $plans = $this->subscriptionService->getAllPlans();

            $stats = [];
            foreach ($plans as $plan) {
                $activeDoctors = $plan->doctorSubscriptions()->active()->count();
                $totalRevenue = $plan->doctorSubscriptions()->sum('amount_paid');

                $stats[] = [
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'active_subscriptions' => $activeDoctors,
                    'total_revenue' => $totalRevenue,
                    'price' => $plan->price,
                ];
            }

            return $this->success($stats, 'تم جلب إحصائيات الاشتراكات بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب الإحصائيات');
        }
    }

    /**
     * Create new subscription plan
     */
    public function store(CreateSubscriptionRequest $request): JsonResponse
    {
        try {
            $plan = $this->subscriptionService->createPlan($request->validated());
            return $this->created($plan, 'تم إنشاء الباقة بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء إنشاء الباقة');
        }
    }

    /**
     * Update subscription plan
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $plan = $this->subscriptionService->updatePlan($id, $request->all());
            return $this->success($plan, 'تم تحديث الباقة بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء تحديث الباقة');
        }
    }

    /**
     * Delete subscription plan
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->subscriptionService->deletePlan($id);
            return $this->success(null, 'تم حذف الباقة بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء حذف الباقة');
        }
    }
}
