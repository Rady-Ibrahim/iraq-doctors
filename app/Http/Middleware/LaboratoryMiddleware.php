<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LaboratoryMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! auth('web')->check()) {
            return redirect()->route('laboratory.login');
        }

        $user = auth('web')->user();

        if (! $user || $user->role !== 'laboratory') {
            auth('web')->logout();

            return redirect()->route('laboratory.login')
                ->withErrors(['phone' => 'غير مصرح لك بالدخول كمعمل.']);
        }

        return $next($request);
    }
}
