<?php

namespace Modules\Subscription\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Subscription\Services\SubscriptionService;
use Modules\Subscription\Http\Requests\CreateSubscriptionRequest;
use Modules\Subscription\Http\Requests\SubscribeDoctorRequest;
use App\Traits\ApiResponse;

class SubscriptionController extends Controller
{
    use ApiResponse;

    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Get all subscription plans
     */
    public function index(): JsonResponse
    {
        try {
            $plans = $this->subscriptionService->getAllPlans();
            return $this->success($plans, 'تم جلب الباقات بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب الباقات');
        }
    }

    /**
     * Get subscription plan by ID
     */
    public function show($id): JsonResponse
    {
        try {
            $plan = $this->subscriptionService->getPlanById($id);
            return $this->success($plan, 'تم جلب الباقة بنجاح');
        } catch (\Exception $e) {
            return $this->notFound('الباقة غير موجودة');
        }
    }

    /**
     * Create new subscription plan (Admin only)
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
     * Update subscription plan (Admin only)
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
     * Delete subscription plan (Admin only)
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

    /**
     * Subscribe doctor to a plan
     */
    public function subscribe(SubscribeDoctorRequest $request): JsonResponse
    {
        try {
            $doctorId = auth('sanctum')->id();

            $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $doctorId)->first();
            if (!$doctor) {
                return $this->notFound('الطبيب غير موجود');
            }

            $subscription = $this->subscriptionService->subscribeDoctor(
                $doctor->id,
                $request->subscription_id,
                $request->validated()
            );

            return $this->created($subscription, 'تم الاشتراك بنجاح');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 'SUBSCRIPTION_FAILED', 400);
        }
    }

    /**
     * Get current doctor subscription
     */
    public function mySubscription(): JsonResponse
    {
        try {
            $userId = auth('sanctum')->id();
            $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $userId)->first();

            if (!$doctor) {
                return $this->notFound('الطبيب غير موجود');
            }

            $subscription = $this->subscriptionService->getDoctorSubscription($doctor->id);

            if (!$subscription) {
                return $this->success(null, 'لا يوجد اشتراك نشط');
            }

            return $this->success($subscription, 'تم جلب الاشتراك بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب الاشتراك');
        }
    }

    /**
     * Renew subscription
     */
    public function renew(): JsonResponse
    {
        try {
            $userId = auth('sanctum')->id();
            $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $userId)->first();

            if (!$doctor) {
                return $this->notFound('الطبيب غير موجود');
            }

            $subscription = $this->subscriptionService->renewSubscription($doctor->id);

            return $this->success($subscription, 'تم تجديد الاشتراك بنجاح');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 'RENEWAL_FAILED', 400);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(): JsonResponse
    {
        try {
            $userId = auth('sanctum')->id();
            $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $userId)->first();

            if (!$doctor) {
                return $this->notFound('الطبيب غير موجود');
            }

            $this->subscriptionService->cancelSubscription($doctor->id);

            return $this->success(null, 'تم إلغاء الاشتراك بنجاح');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 'CANCELLATION_FAILED', 400);
        }
    }

    /**
     * Check subscription limit
     */
    public function checkLimit(): JsonResponse
    {
        try {
            $userId = auth('sanctum')->id();
            $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $userId)->first();

            if (!$doctor) {
                return $this->notFound('الطبيب غير موجود');
            }

            $canBook = $this->subscriptionService->checkDoctorSubscriptionLimit($doctor->id);

            return $this->success([
                'can_book' => $canBook,
            ], $canBook ? 'يمكنك حجز المواعيد' : 'لقد وصلت للحد الأقصى من الحجوزات');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء التحقق');
        }
    }
}
