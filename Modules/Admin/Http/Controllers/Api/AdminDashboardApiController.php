<?php

namespace Modules\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Services\AdminDashboardService;
use App\Traits\ApiResponse;

class AdminDashboardApiController extends Controller
{
    use ApiResponse;

    public function __construct(private AdminDashboardService $adminDashboardService) {}

    public function metrics(): JsonResponse
    {
        try {
            return $this->success($this->adminDashboardService->getSystemMetrics());
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب المقاييس');
        }
    }

    public function doctors(Request $request): JsonResponse
    {
        try {
            $paginator = $this->adminDashboardService->getDoctorsStats($request->all());
            $paginator->getCollection()->transform(fn ($doctor) => $this->formatDoctor($doctor));

            if ($request->has('limit') && !$request->has('page')) {
                return $this->success($paginator->items());
            }

            return $this->paginated(
                $paginator->items(),
                $paginator->total(),
                $paginator->currentPage(),
                $paginator->perPage()
            );
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب الأطباء');
        }
    }

    public function doctorDetails($id): JsonResponse
    {
        try {
            return $this->success($this->adminDashboardService->getDoctorDetails((int) $id));
        } catch (\Exception $e) {
            return $this->notFound('الطبيب غير موجود');
        }
    }

    public function destroyDoctor($id): JsonResponse
    {
        try {
            $this->adminDashboardService->deleteDoctor((int) $id);
            return $this->success(null, 'تم حذف الطبيب بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء حذف الطبيب');
        }
    }

    public function patients(Request $request): JsonResponse
    {
        try {
            $paginator = $this->adminDashboardService->getPatientsStats($request->all());
            return $this->paginated(
                $paginator->items(),
                $paginator->total(),
                $paginator->currentPage(),
                $paginator->perPage()
            );
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب المرضى');
        }
    }

    public function patientDetails($id): JsonResponse
    {
        try {
            $patient = \Modules\Auth\Models\User::where('role', 'patient')->findOrFail($id);
            return $this->success($patient);
        } catch (\Exception $e) {
            return $this->notFound('المريض غير موجود');
        }
    }

    public function appointments(Request $request): JsonResponse
    {
        try {
            $paginator = $this->adminDashboardService->getAppointmentsStats($request->all());

            if ($request->has('limit') && !$request->has('page')) {
                return $this->success($paginator->items());
            }

            return $this->paginated(
                $paginator->items(),
                $paginator->total(),
                $paginator->currentPage(),
                $paginator->perPage()
            );
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب المواعيد');
        }
    }

    public function revenue(Request $request): JsonResponse
    {
        try {
            $data = $this->adminDashboardService->getRevenueDashboardData($request->all());
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب الإيرادات');
        }
    }

    public function subscriptions(Request $request): JsonResponse
    {
        try {
            $data = $this->adminDashboardService->getSubscriptionsDashboardData($request->all());
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب الاشتراكات');
        }
    }

    public function analytics(Request $request): JsonResponse
    {
        try {
            $data = $this->adminDashboardService->getAnalyticsData(
                $request->get('period', 'month'),
                $request->get('type')
            );
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب التحليلات');
        }
    }

    public function approveDoctor($id): JsonResponse
    {
        try {
            $doctor = $this->adminDashboardService->approveDoctor($id);
            return $this->success($doctor, 'تم تفعيل حساب الطبيب بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء تفعيل حساب الطبيب');
        }
    }

    public function rejectDoctor(Request $request, $id): JsonResponse
    {
        try {
            $doctor = $this->adminDashboardService->rejectDoctor($id, $request->input('reject_reason'));
            return $this->success($doctor, 'تم رفض حساب الطبيب بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء رفض حساب الطبيب');
        }
    }

    public function suspendDoctor($id): JsonResponse
    {
        try {
            $doctor = $this->adminDashboardService->suspendDoctor($id);
            return $this->success($doctor, 'تم تعليق حساب الطبيب بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء تعليق حساب الطبيب');
        }
    }

    public function activateDoctor($id): JsonResponse
    {
        try {
            $doctor = $this->adminDashboardService->activateDoctor($id);
            return $this->success($doctor, 'تم تفعيل حساب الطبيب بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء تفعيل حساب الطبيب');
        }
    }

    public function blockPatient($id): JsonResponse
    {
        try {
            $patient = \Modules\Auth\Models\User::where('role', 'patient')->findOrFail($id);
            $patient->update(['status' => 'blocked']);
            return $this->success($patient, 'تم حظر المريض بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء حظر المريض');
        }
    }

    public function unblockPatient($id): JsonResponse
    {
        try {
            $patient = \Modules\Auth\Models\User::where('role', 'patient')->findOrFail($id);
            $patient->update(['status' => 'active']);
            return $this->success($patient, 'تم فك الحظر عن المريض بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء فك الحظر');
        }
    }

    public function deletePatient($id): JsonResponse
    {
        try {
            $patient = \Modules\Auth\Models\User::where('role', 'patient')->findOrFail($id);
            $patient->delete();
            return $this->success(null, 'تم حذف المريض بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء حذف المريض');
        }
    }

    public function resetPatientPassword($id): JsonResponse
    {
        try {
            $patient = \Modules\Auth\Models\User::where('role', 'patient')->findOrFail($id);
            $patient->update(['password' => 'password123']);
            return $this->success(null, 'تم إعادة تعيين كلمة المرور بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء إعادة تعيين كلمة المرور');
        }
    }

    public function confirmAppointment($id): JsonResponse
    {
        try {
            $appointment = \Modules\Appointment\Models\Appointment::findOrFail($id);
            $appointment->update(['status' => 'confirmed']);
            return $this->success($appointment, 'تم تأكيد الموعد بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء تأكيد الموعد');
        }
    }

    public function cancelAppointment($id): JsonResponse
    {
        try {
            $appointment = \Modules\Appointment\Models\Appointment::findOrFail($id);
            $appointment->update(['status' => 'cancelled']);
            return $this->success($appointment, 'تم إلغاء الموعد بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء إلغاء الموعد');
        }
    }

    protected function formatDoctor($doctor): array
    {
        return [
            'id' => $doctor->id,
            'name' => $doctor->user?->name,
            'phone' => $doctor->user?->phone,
            'email' => $doctor->user?->email,
            'speciality' => $doctor->speciality?->name_ar,
            'speciality_id' => $doctor->speciality_id,
            'status' => $doctor->status,
            'rating' => $doctor->rating,
            'experience_years' => $doctor->experience_years,
            'created_at' => $doctor->created_at,
        ];
    }
}
