<?php

namespace App\Http\Middleware;

use App\Support\DoctorDashboardContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Modules\Doctor\Models\Doctor;
use Modules\Doctor\Models\DoctorStaffMember;

class DoctorApprovedMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth('web')->user();

        if ($user?->isDoctorStaff()) {
            $staffMember = DoctorStaffMember::with(['doctor.user'])
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (! $staffMember) {
                if ($request->expectsJson() || $request->is('doctor/api/*')) {
                    return response()->json([
                        'success' => false,
                        'error' => ['code' => 'STAFF_INACTIVE', 'message' => 'حساب السكرتير غير نشط'],
                    ], 403);
                }

                auth('web')->logout();

                return redirect()->route('doctor.login')
                    ->withErrors(['phone' => 'حساب السكرتير غير نشط. يرجى التواصل مع الطبيب.']);
            }

            $doctor = $staffMember->doctor;
        } else {
            $doctor = Doctor::where('user_id', auth('web')->id())->first();
        }

        if (! $doctor) {
            if ($request->expectsJson() || $request->is('doctor/api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'DOCTOR_NOT_FOUND', 'message' => 'ملف الطبيب غير موجود'],
                ], 403);
            }

            auth('web')->logout();

            return redirect()->route('doctor.login')
                ->withErrors(['phone' => 'ملف الطبيب غير موجود. يرجى التواصل مع الإدارة.']);
        }

        if ($doctor->status === 'approved') {
            try {
                $context = DoctorDashboardContext::make();
                app()->instance(DoctorDashboardContext::class, $context);
                View::share('doctorDashboard', $context);
                View::share('isDoctorOwner', auth('web')->user()?->isDoctor() ?? false);
            } catch (\Throwable) {
                // Fallback: doctor record is still valid for approved access.
            }

            return $next($request);
        }

        if ($request->expectsJson() || $request->is('doctor/api/*')) {
            $message = match ($doctor->status) {
                'pending' => 'حسابك قيد المراجعة من قبل الإدارة',
                'rejected' => 'تم رفض حسابك. يرجى إعادة رفع المستندات',
                'suspended' => 'تم تعليق حسابك',
                default => 'غير مصرح لك بالوصول',
            };

            return response()->json([
                'success' => false,
                'error' => ['code' => 'DOCTOR_NOT_APPROVED', 'message' => $message],
            ], 403);
        }

        return match ($doctor->status) {
            'pending' => redirect()->route('doctor.pending'),
            'rejected' => redirect()->route('doctor.rejected'),
            'suspended' => redirect()->route('doctor.suspended'),
            default => redirect()->route('doctor.pending'),
        };
    }
}
