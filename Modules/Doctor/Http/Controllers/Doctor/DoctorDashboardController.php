<?php

namespace Modules\Doctor\Http\Controllers\Doctor;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Doctor\Services\DoctorDashboardService;
use App\Traits\ApiResponse;

class DoctorDashboardController extends Controller
{
    use ApiResponse;

    protected $doctorDashboardService;

    public function __construct(DoctorDashboardService $doctorDashboardService)
    {
        $this->doctorDashboardService = $doctorDashboardService;
    }

    /**
     * Get doctor dashboard metrics
     */
    public function metrics(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $userId)->firstOrFail();
            
            $metrics = $this->doctorDashboardService->getMetrics($doctor->id);
            
            return $this->success($metrics, 'تم جلب المقاييس بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب المقاييس');
        }
    }

    /**
     * Get doctor's patients list
     */
    public function patients(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $userId)->firstOrFail();
            
            $filters = $request->only(['search']);
            $patients = $this->doctorDashboardService->getPatientsList($doctor->id, $filters);
            
            return $this->success($patients, 'تم جلب قائمة المرضى بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب قائمة المرضى');
        }
    }

    /**
     * Get patient details with medical history
     */
    public function patientDetails($patientId): JsonResponse
    {
        try {
            $userId = auth()->id();
            $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $userId)->firstOrFail();
            
            $details = $this->doctorDashboardService->getPatientDetails($doctor->id, $patientId);
            
            return $this->success($details, 'تم جلب تفاصيل المريض بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب تفاصيل المريض');
        }
    }

    /**
     * Get today's activity
     */
    public function todayActivity(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $userId)->firstOrFail();
            
            $activity = $this->doctorDashboardService->getTodayActivity($doctor->id);
            
            return $this->success($activity, 'تم جلب نشاط اليوم بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب نشاط اليوم');
        }
    }

    /**
     * Get upcoming tasks and appointments
     */
    public function upcomingTasks(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $userId)->firstOrFail();
            
            $tasks = $this->doctorDashboardService->getUpcomingTasks($doctor->id);
            
            return $this->success($tasks, 'تم جلب المهام القادمة بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب المهام القادمة');
        }
    }

    /**
     * Get prescriptions list
     */
    public function prescriptions(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $userId)->firstOrFail();
            
            $filters = $request->only(['patient_id', 'date_from', 'date_to']);
            $prescriptions = $this->doctorDashboardService->getPrescriptions($doctor->id, $filters);
            
            return $this->success($prescriptions, 'تم جلب الوصفات الطبية بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب الوصفات الطبية');
        }
    }

    /**
     * Get medical records
     */
    public function records(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $userId)->firstOrFail();
            
            $filters = $request->only(['type', 'patient_id']);
            $records = $this->doctorDashboardService->getRecords($doctor->id, $filters);
            
            return $this->success($records, 'تم جلب السجلات الطبية بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب السجلات الطبية');
        }
    }

    /**
     * Get patient prescriptions
     */
    public function patientPrescriptions($patientId): JsonResponse
    {
        try {
            $userId = auth()->id();
            $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $userId)->firstOrFail();
            
            $prescriptions = $this->doctorDashboardService->getPatientPrescriptions($doctor->id, $patientId);
            
            return $this->success($prescriptions, 'تم جلب وصفات المريض بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب وصفات المريض');
        }
    }
}
