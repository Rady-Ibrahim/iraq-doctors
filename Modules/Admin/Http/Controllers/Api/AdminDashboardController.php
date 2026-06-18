<?php

namespace Modules\Admin\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Services\AdminDashboardService;

class AdminDashboardController extends Controller
{
    protected $adminDashboardService;

    public function __construct(AdminDashboardService $adminDashboardService)
    {
        $this->adminDashboardService = $adminDashboardService;
    }

    /**
     * Get system-wide metrics and display dashboard
     */
    public function metrics()
    {
        try {
            $metrics = $this->adminDashboardService->getSystemMetrics();
            return view('admin.dashboard', compact('metrics'));
        } catch (\Exception $e) {
            abort(500, 'حدث خطأ أثناء جلب المقاييس');
        }
    }

    /**
     * Get doctors statistics and display table
     */
    public function doctorsStats(Request $request)
    {
        try {
            $filters = $request->only(['status', 'speciality_id', 'search']);
            $doctors = $this->adminDashboardService->getDoctorsStats($filters);
            return view('admin.doctors.index', compact('doctors', 'filters'));
        } catch (\Exception $e) {
            abort(500, 'حدث خطأ أثناء جلب إحصائيات الأطباء');
        }
    }

    /**
     * Get patients statistics
     */
    public function patientsStats(Request $request)
    {
        try {
            $filters = $request->only(['status', 'is_ghost', 'search']);
            $patients = $this->adminDashboardService->getPatientsStats($filters);
            return view('admin.patients.index', compact('patients', 'filters'));
        } catch (\Exception $e) {
            abort(500, 'حدث خطأ أثناء جلب إحصائيات المرضى');
        }
    }

    /**
     * Get appointments statistics
     */
    public function appointmentsStats(Request $request)
    {
        try {
            $filters = $request->only(['status', 'date_from', 'date_to']);
            $appointments = $this->adminDashboardService->getAppointmentsStats($filters);
            return view('admin.appointments.index', compact('appointments', 'filters'));
        } catch (\Exception $e) {
            abort(500, 'حدث خطأ أثناء جلب إحصائيات Mواعيد');
        }
    }

    /**
     * Get revenue statistics
     */
    public function revenueStats(Request $request)
    {
        try {
            $filters = $request->only(['date_from', 'date_to', 'status']);
            $revenue = $this->adminDashboardService->getRevenueStats($filters);
            return view('admin.revenue', compact('revenue', 'filters'));
        } catch (\Exception $e) {
            abort(500, 'حدث خطأ أثناء جلب إحصائيات الإيرادات');
        }
    }

    /**
     * Get analytics data for charts
     */
    public function analytics(Request $request)
    {
        try {
            $period = $request->get('period', '30days');
            $analytics = $this->adminDashboardService->getAnalyticsData($period);
            return view('admin.analytics', compact('analytics', 'period'));
        } catch (\Exception $e) {
            abort(500, 'حدث خطأ أثناء جلب البيانات التحليلية');
        }
    }

    /* ── Actions (إجراءات الأطباء مع عمل توجيه للخلف بالـ Sessions لرسائل النجاح) ── */

    public function approveDoctor($id)
    {
        try {
            $this->adminDashboardService->approveDoctor($id);
            return redirect()->back()->with('success', 'تم تفعيل حساب الطبيب بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء تفعيل حساب الطبيب');
        }
    }

    public function rejectDoctor($id)
    {
        try {
            $this->adminDashboardService->rejectDoctor($id);
            return redirect()->back()->with('success', 'تم رفض حساب الطبيب بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء رفض حساب الطبيب');
        }
    }

    public function suspendDoctor($id)
    {
        try {
            $this->adminDashboardService->suspendDoctor($id);
            return redirect()->back()->with('success', 'تم تعليق حساب الطبيب بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء تعليق حساب الطبيب');
        }
    }

    public function activateDoctor($id)
    {
        try {
            $this->adminDashboardService->activateDoctor($id);
            return redirect()->back()->with('success', 'تم تفعيل حساب الطبيب بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء تفعيل حساب الطبيب');
        }
    }
}