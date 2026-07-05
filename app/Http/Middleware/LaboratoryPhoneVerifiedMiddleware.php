<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LaboratoryPhoneVerifiedMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth('web')->user();

        if ($user && $user->isLaboratory() && ! $user->phone_verified_at) {
            return redirect()->route('laboratory.verify-phone')
                ->with('warning', 'يرجى تفعيل رقم هاتفك للمتابعة.');
        }

        return $next($request);
    }
}
