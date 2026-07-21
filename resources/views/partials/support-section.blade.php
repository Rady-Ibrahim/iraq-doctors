{{--
  Reusable support contacts section.
  Params:
    $accentColor   : Tailwind color name  (e.g. 'teal', 'emerald', 'indigo')
    $apiPrefix     : URL prefix           (e.g. '/doctor/api', '/pharmacy/api', '/laboratory/api')
--}}
@php
  $color  = $accentColor  ?? 'teal';
  $prefix = $apiPrefix    ?? '/doctor/api';
@endphp

<div class="bg-white rounded-xl shadow-sm p-6 mb-4">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">إضافة خدمة دعم</h3>
    <form id="supportAddForm" onsubmit="supportSave(event)"
          class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">اسم الخدمة *</label>
            <input type="text" id="svc_name" required placeholder="مثل: الدعم الفني"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-{{ $color }}-500 text-sm">
            <span class="text-red-500 text-xs" data-error-for="service_name"></span>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">رقم الواتساب</label>
            <input type="text" id="svc_whatsapp" placeholder="9647xxxxxxxxx"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-{{ $color }}-500 text-sm">
            <span class="text-red-500 text-xs" data-error-for="whatsapp_phone"></span>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">رقم الاتصال</label>
            <input type="text" id="svc_call" placeholder="07xxxxxxxxx"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-{{ $color }}-500 text-sm">
            <span class="text-red-500 text-xs" data-error-for="call_phone"></span>
        </div>
        <div class="flex items-end">
            <button type="submit"
                class="w-full px-4 py-2 bg-{{ $color }}-600 text-white rounded-lg hover:bg-{{ $color }}-700 transition text-sm">
                <i class="fas fa-plus ml-1"></i> إضافة
            </button>
        </div>
    </form>
    <p class="text-xs text-gray-400 mt-2">أدخل رقم واتساب أو رقم اتصال على الأقل.</p>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">اسم الخدمة</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">واتساب</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">اتصال</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">الحالة</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">إجراءات</th>
            </tr>
        </thead>
        <tbody id="supportTableBody">
            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">جاري التحميل...</td></tr>
        </tbody>
    </table>
</div>

<!-- Edit Modal -->
<div id="supportEditModal"
     class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">تعديل خدمة الدعم</h3>
            <button onclick="supportCloseEdit()"
                class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="supportEditForm" onsubmit="supportUpdate(event)" class="space-y-3">
            <input type="hidden" id="svc_edit_id">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">اسم الخدمة *</label>
                <input type="text" id="svc_edit_name" required
                    class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">رقم الواتساب</label>
                <input type="text" id="svc_edit_whatsapp"
                    class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">رقم الاتصال</label>
                <input type="text" id="svc_edit_call"
                    class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
            <div class="flex gap-3 pt-1">
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-{{ $color }}-600 text-white rounded-lg hover:bg-{{ $color }}-700 text-sm">
                    حفظ
                </button>
                <button type="button" onclick="supportCloseEdit()"
                    class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Support section (scoped to avoid naming conflicts) ──────────────
(function() {
    const API = '{{ $prefix }}/support-contacts';
    let items = [];

    async function load() {
        const data = await apiCall(API);
        const tbody = document.getElementById('supportTableBody');
        if (!data?.success || !data.data?.length) {
            items = [];
            tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">لا توجد خدمات دعم بعد</td></tr>';
            return;
        }
        items = data.data;
        tbody.innerHTML = items.map(i => `
            <tr class="border-b hover:bg-gray-50">
                <td class="px-4 py-3 font-medium">${esc(i.service_name)}</td>
                <td class="px-4 py-3 font-mono">${i.whatsapp_phone
                    ? `<a href="https://wa.me/${i.whatsapp_phone.replace(/\D/g,'')}" target="_blank"
                           class="text-green-600 hover:underline">${esc(i.whatsapp_phone)}</a>`
                    : '<span class="text-gray-300">—</span>'}</td>
                <td class="px-4 py-3 font-mono">${i.call_phone
                    ? `<a href="tel:${i.call_phone}" class="text-blue-600 hover:underline">${esc(i.call_phone)}</a>`
                    : '<span class="text-gray-300">—</span>'}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-0.5 rounded-full text-xs ${i.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}">
                        ${i.is_active ? 'نشط' : 'موقوف'}
                    </span>
                </td>
                <td class="px-4 py-3 flex gap-1 flex-wrap">
                    <button onclick="supportOpenEdit(${i.id})"
                        class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs hover:bg-gray-200">تعديل</button>
                    <button onclick="supportToggle(${i.id}, ${i.is_active})"
                        class="px-2 py-1 ${i.is_active ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'} rounded text-xs">
                        ${i.is_active ? 'إيقاف' : 'تفعيل'}
                    </button>
                    <button onclick="supportDelete(${i.id})"
                        class="px-2 py-1 bg-red-100 text-red-600 rounded text-xs hover:bg-red-200">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    window.supportSave = async function(e) {
        e.preventDefault();
        clearFieldErrors?.();
        const name = document.getElementById('svc_name').value.trim();
        const whatsapp = document.getElementById('svc_whatsapp').value.trim();
        const call = document.getElementById('svc_call').value.trim();
        if (!whatsapp && !call) {
            alert('أدخل رقم واتساب أو رقم اتصال على الأقل');
            return;
        }
        const data = await apiCall(API, {
            method: 'POST',
            body: JSON.stringify({ service_name: name, whatsapp_phone: whatsapp || null, call_phone: call || null, is_active: true }),
        });
        if (data?.success) {
            showSuccess?.(data.message || 'تم الإضافة');
            document.getElementById('supportAddForm').reset();
            load();
        } else { handleApiError?.(data); }
    };

    window.supportOpenEdit = function(id) {
        const item = items.find(i => i.id === id);
        if (!item) return;
        document.getElementById('svc_edit_id').value = item.id;
        document.getElementById('svc_edit_name').value = item.service_name || '';
        document.getElementById('svc_edit_whatsapp').value = item.whatsapp_phone || '';
        document.getElementById('svc_edit_call').value = item.call_phone || '';
        const modal = document.getElementById('supportEditModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    window.supportCloseEdit = function() {
        const modal = document.getElementById('supportEditModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    window.supportUpdate = async function(e) {
        e.preventDefault();
        const id = document.getElementById('svc_edit_id').value;
        const data = await apiCall(`${API}/${id}`, {
            method: 'PUT',
            body: JSON.stringify({
                service_name: document.getElementById('svc_edit_name').value.trim(),
                whatsapp_phone: document.getElementById('svc_edit_whatsapp').value.trim() || null,
                call_phone: document.getElementById('svc_edit_call').value.trim() || null,
            }),
        });
        if (data?.success) { showSuccess?.(data.message || 'تم التحديث'); supportCloseEdit(); load(); }
        else { handleApiError?.(data); }
    };

    window.supportToggle = async function(id, isActive) {
        const data = await apiCall(`${API}/${id}`, {
            method: 'PUT',
            body: JSON.stringify({ is_active: !isActive }),
        });
        if (data?.success) load();
    };

    window.supportDelete = async function(id) {
        if (!await confirmAction?.('حذف خدمة الدعم هذه؟')) return;
        const data = await apiCall(`${API}/${id}`, { method: 'DELETE' });
        if (data?.success) { showSuccess?.('تم الحذف'); load(); }
    };

    function esc(text) {
        return String(text ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // Auto-load when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', load);
    } else {
        load();
    }
})();
</script>
