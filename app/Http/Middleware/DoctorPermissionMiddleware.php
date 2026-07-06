<?php

namespace App\Http\Middleware;

use App\Support\DoctorDashboardContext;
use Closure;
use Illuminate\Http\Request;

class DoctorPermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        try {
            $context = DoctorDashboardContext::resolve();
        } catch (\Throwable) {
            if ($request->expectsJson() || $request->is('doctor/api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'FORBIDDEN', 'message' => 'غير مصرح لك بالوصول'],
                ], 403);
            }

            return redirect()->route('doctor.dashboard')
                ->withErrors(['permission' => 'غير مصرح لك بالوصول إلى هذه الصفحة']);
        }

        if (! $context->hasPermission($permission)) {
            if ($request->expectsJson() || $request->is('doctor/api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'FORBIDDEN', 'message' => 'ليس لديك صلاحية لهذا الإجراء'],
                ], 403);
            }

            return redirect()->route('doctor.dashboard')
                ->withErrors(['permission' => 'ليس لديك صلاحية للوصول إلى هذه الصفحة']);
        }

        return $next($request);
    }
}
