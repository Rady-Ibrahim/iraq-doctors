<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoctorMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول أولاً'
            ], 401);
        }

        $user = Auth::user();

        if ($user->role !== 'doctor') {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك الصلاحية للوصول لهذا المورد'
            ], 403);
        }

        return $next($request);
    }
}
