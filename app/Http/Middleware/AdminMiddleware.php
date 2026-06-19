<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // شيك على جلسة الـ Web فقط - مالناش دعوة بـ Sanctum
        if (!auth('web')->check()) {
            return redirect()->route('admin.login');
        }

        $user = auth('web')->user();

        // التأكد من أن المستخدم أدمن فعلاً
        if (!$user || $user->role !== 'admin') {
            auth('web')->logout();
            return redirect()->route('admin.login')->withErrors(['phone' => 'غير مصرح لك بالدخول كأدمن.']);
        }

        return $next($request);
    }
}