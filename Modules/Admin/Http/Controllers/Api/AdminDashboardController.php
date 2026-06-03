<?php

namespace Modules\Admin\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Admin\Services\AdminDashboardService;
use App\Traits\ApiResponse;

class AdminDashboardController extends Controller
{
    use ApiResponse;

    protected $adminDashboardService;

    public function __construct(AdminDashboardService $adminDashboardService)
    {
        $this->adminDashboardService = $adminDashboardService;
    }

    /**
     * Get system-wide metrics
     */
    public function metrics(): JsonResponse
    {
        try {
            $metrics = $this->adminDashboardService->getSystemMetrics();
            return $this->success($metrics, 'تم جلب المقاييس بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب المقاييس');
        }
    }

    /**
     * Get doctors statistics
     */
    public function doctorsStats(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['status', 'speciality_id', 'search']);
            $doctors = $this->adminDashboardService->getDoctorsStats($filters);
            return $this->success($doctors, 'تم جلب إحصائيات الأطباء بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب إحصائيات الأطباء');
        }
    }

    /**
     * Get patients statistics
     */
    public function patientsStats(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['status', 'is_ghost', 'search']);
            $patients = $this->adminDashboardService->getPatientsStats($filters);
            return $this->success($patients, 'تم جلب إحصائيات المرضى بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب إحصائيات المرضى');
        }
    }

    /**
     * Get appointments statistics
     */
    public function appointmentsStats(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['status', 'date_from', 'date_to']);
            $appointments = $this->adminDashboardService->getAppointmentsStats($filters);
            return $this->success($appointments, 'تم جلب إحصائيات المواعيد بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب إحصائيات المواعيد');
        }
    }

    /**
     * Get revenue statistics
     */
    public function revenueStats(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['date_from', 'date_to', 'status']);
            $revenue = $this->adminDashboardService->getRevenueStats($filters);
            return $this->success($revenue, 'تم جلب إحصائيات الإيرادات بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب إحصائيات الإيرادات');
        }
    }

    /**
     * Get analytics data for charts
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '30days');
            $analytics = $this->adminDashboardService->getAnalyticsData($period);
            return $this->success($analytics, 'تم جلب البيانات التحليلية بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب البيانات التحليلية');
        }
    }

    /**
     * Approve doctor
     */
    public function approveDoctor($id): JsonResponse
    {
        try {
            $doctor = $this->adminDashboardService->approveDoctor($id);
            return $this->success($doctor, 'تم تفعيل حساب الطبيب بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء تفعيل حساب الطبيب');
        }
    }

    /**
     * Reject doctor
     */
    public function rejectDoctor($id): JsonResponse
    {
        try {
            $doctor = $this->adminDashboardService->rejectDoctor($id);
            return $this->success($doctor, 'تم رفض حساب الطبيب بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء رفض حساب الطبيب');
        }
    }

    /**
     * Suspend doctor
     */
    public function suspendDoctor($id): JsonResponse
    {
        try {
            $doctor = $this->adminDashboardService->suspendDoctor($id);
            return $this->success($doctor, 'تم تعليق حساب الطبيب بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء تعليق حساب الطبيب');
        }
    }

    /**
     * Activate doctor
     */
    public function activateDoctor($id): JsonResponse
    {
        try {
            $doctor = $this->adminDashboardService->activateDoctor($id);
            return $this->success($doctor, 'تم تفعيل حساب الطبيب بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء تفعيل حساب الطبيب');
        }
    }
}
