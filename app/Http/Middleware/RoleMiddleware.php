<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول أولاً'
            ], 401);
        }

        $user = Auth::user();

        // Check if user has the required role
        if ($user->role !== $role) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك الصلاحية للوصول لهذا المورد'
            ], 403);
        }

        return $next($request);
    }
}
