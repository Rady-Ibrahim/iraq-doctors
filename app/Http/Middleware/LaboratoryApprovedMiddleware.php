<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Laboratory\Models\Laboratory;

class LaboratoryApprovedMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $laboratory = Laboratory::where('user_id', auth('web')->id())->first();

        if (! $laboratory) {
            if ($this->expectsDashboardJson($request)) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'LABORATORY_NOT_FOUND', 'message' => 'ملف المختبر غير موجود'],
                ], 403);
            }

            auth('web')->logout();

            return redirect()->route('laboratory.login')
                ->withErrors(['phone' => 'ملف المختبر غير موجود. يرجى التواصل مع الإدارة.']);
        }

        if ($laboratory->status === 'approved') {
            return $next($request);
        }

        if ($this->expectsDashboardJson($request)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'LABORATORY_NOT_APPROVED',
                    'message' => $this->statusMessage($laboratory->status),
                ],
            ], 403);
        }

        return match ($laboratory->status) {
            'pending' => redirect()->route('laboratory.pending'),
            'rejected' => redirect()->route('laboratory.rejected'),
            'suspended' => redirect()->route('laboratory.suspended'),
            default => redirect()->route('laboratory.pending'),
        };
    }

    private function expectsDashboardJson(Request $request): bool
    {
        return $request->expectsJson() || $request->is('laboratory/api/*');
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
