<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DoctorMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // شيك على جلسة الـ Web فقط - مالناش دعوة بـ Sanctum
        if (!auth('web')->check()) {
            return redirect()->route('doctor.login');
        }

        $user = auth('web')->user();
        
        // التأكد من أن المستخدم دكتور فعلاً
        if (!$user || $user->role !== 'doctor') {
            auth('web')->logout();
            return redirect()->route('doctor.login')->withErrors(['phone' => 'غير مصرح لك بالدخول كدكتور.']);
        }

        return $next($request);
    }
}