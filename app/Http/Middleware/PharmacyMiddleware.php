<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PharmacyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! auth('web')->check()) {
            return redirect()->route('pharmacy.login');
        }

        $user = auth('web')->user();

        if (! $user || $user->role !== 'pharmacy') {
            auth('web')->logout();

            return redirect()->route('pharmacy.login')
                ->withErrors(['phone' => 'غير مصرح لك بالدخول كصيدلية.']);
        }

        return $next($request);
    }
}
