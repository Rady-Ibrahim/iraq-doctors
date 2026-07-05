<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Pharmacy\Models\Pharmacy;

class PharmacyApprovedMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $pharmacy = Pharmacy::where('user_id', auth('web')->id())->first();

        if (! $pharmacy) {
            if ($this->expectsDashboardJson($request)) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'PHARMACY_NOT_FOUND', 'message' => 'ملف الصيدلية غير موجود'],
                ], 403);
            }

            auth('web')->logout();

            return redirect()->route('pharmacy.login')
                ->withErrors(['phone' => 'ملف الصيدلية غير موجود. يرجى التواصل مع الإدارة.']);
        }

        if ($pharmacy->status === 'approved') {
            return $next($request);
        }

        if ($this->expectsDashboardJson($request)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PHARMACY_NOT_APPROVED',
                    'message' => $this->statusMessage($pharmacy->status),
                ],
            ], 403);
        }

        return match ($pharmacy->status) {
            'pending' => redirect()->route('pharmacy.pending'),
            'rejected' => redirect()->route('pharmacy.rejected'),
            'suspended' => redirect()->route('pharmacy.suspended'),
            default => redirect()->route('pharmacy.pending'),
        };
    }

    private function expectsDashboardJson(Request $request): bool
    {
        return $request->expectsJson() || $request->is('pharmacy/api/*');
    }

    private function statusMessage(string $status): string
    {
        return match ($status) {
            'pending' => 'حسابك قيد المراجعة من قبل الإدارة',
            'rejected' => 'تم رفض حسابك. يرجى إعادة رفع المستندات',
            'suspended' => 'تم تعليق حسابك',
            default => 'غير مصرح لك بالوصول',
        };
    }
}
