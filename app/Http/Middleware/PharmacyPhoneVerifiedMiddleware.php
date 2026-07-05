<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PharmacyPhoneVerifiedMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth('web')->user();

        if ($user && $user->isPharmacy() && ! $user->phone_verified_at) {
            return redirect()->route('pharmacy.verify-phone')
                ->with('warning', 'يرجى تفعيل رقم هاتفك للمتابعة.');
        }

        return $next($request);
    }
}
