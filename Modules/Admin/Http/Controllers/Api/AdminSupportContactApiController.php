<?php

namespace Modules\Admin\Http\Controllers\Api;

use App\Models\SupportContact;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AdminSupportContactApiController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $items = SupportContact::ordered()->get();

        return $this->success($items);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_name' => 'required|string|max:255',
            'whatsapp_phone' => 'nullable|string|max:30',
            'call_phone' => 'nullable|string|max:30',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ], [
            'service_name.required' => 'اسم الخدمة مطلوب',
        ], [
            'service_name' => 'اسم الخدمة',
            'whatsapp_phone' => 'رقم الواتساب',
            'call_phone' => 'رقم الاتصال',
            'sort_order' => 'الترتيب',
        ]);

        if (empty($validated['whatsapp_phone']) && empty($validated['call_phone'])) {
            return $this->error('يجب إدخال رقم واتساب أو رقم اتصال على الأقل', 'VALIDATION_ERROR', 422);
        }

        $item = SupportContact::create([
            'service_name' => $validated['service_name'],
            'whatsapp_phone' => $validated['whatsapp_phone'] ?? null,
            'call_phone' => $validated['call_phone'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return $this->created($item, 'تم إضافة خدمة الدعم بنجاح');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = SupportContact::findOrFail($id);

        $validated = $request->validate([
            'service_name' => 'sometimes|string|max:255',
            'whatsapp_phone' => 'nullable|string|max:30',
            'call_phone' => 'nullable|string|max:30',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ], [], [
            'service_name' => 'اسم الخدمة',
            'whatsapp_phone' => 'رقم الواتساب',
            'call_phone' => 'رقم الاتصال',
            'sort_order' => 'الترتيب',
        ]);

        $whatsapp = array_key_exists('whatsapp_phone', $validated)
            ? $validated['whatsapp_phone']
            : $item->whatsapp_phone;
        $call = array_key_exists('call_phone', $validated)
            ? $validated['call_phone']
            : $item->call_phone;

        if (empty($whatsapp) && empty($call)) {
            return $this->error('يجب إدخال رقم واتساب أو رقم اتصال على الأقل', 'VALIDATION_ERROR', 422);
        }

        $item->update($validated);

        return $this->success($item->fresh(), 'تم تحديث خدمة الدعم بنجاح');
    }

    public function destroy(int $id): JsonResponse
    {
        SupportContact::findOrFail($id)->delete();

        return $this->success(null, 'تم حذف خدمة الدعم بنجاح');
    }
}
