<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DoctorOwnerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth('web')->user();

        if (! $user || ! $user->isDoctor()) {
            if ($request->expectsJson() || $request->is('doctor/api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'FORBIDDEN', 'message' => 'هذا الإجراء متاح للطبيب فقط'],
                ], 403);
            }

            return redirect()->route('doctor.dashboard')
                ->withErrors(['permission' => 'هذا الإجراء متاح للطبيب فقط']);
        }

        return $next($request);
    }
}
