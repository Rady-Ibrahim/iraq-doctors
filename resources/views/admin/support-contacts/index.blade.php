@extends('admin.layout')

@section('title', 'الدعم')
@section('page-title', 'الدعم')
@section('page-description', 'أرقام الدعم المعروضة في تطبيق الموبايل')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">إضافة خدمة دعم</h3>
    <form id="supportForm" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4" onsubmit="saveSupport(event)">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">اسم الخدمة</label>
            <input type="text" id="serviceName" required placeholder="مثل: الدعم الفني"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            <span class="text-red-500 text-sm" data-error-for="service_name"></span>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">رقم الواتساب</label>
            <input type="text" id="whatsappPhone" placeholder="9647xxxxxxxxx"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            <span class="text-red-500 text-sm" data-error-for="whatsapp_phone"></span>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">رقم الاتصال</label>
            <input type="text" id="callPhone" placeholder="07xxxxxxxxx"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            <span class="text-red-500 text-sm" data-error-for="call_phone"></span>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">الترتيب</label>
            <input type="number" id="sortOrder" min="0" value="0"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg">
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-plus ml-2"></i>إضافة
            </button>
        </div>
    </form>
    <p class="text-xs text-gray-500 mt-3">أدخل رقم واتساب أو رقم اتصال على الأقل. الأرقام النشطة تظهر في الموبايل.</p>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الترتيب</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">اسم الخدمة</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">واتساب</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">اتصال</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الحالة</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الإجراءات</th>
            </tr>
        </thead>
        <tbody id="supportTableBody">
            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">جاري التحميل...</td></tr>
        </tbody>
    </table>
</div>

<div id="editModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">تعديل خدمة الدعم</h3>
        <form id="editForm" onsubmit="updateSupport(event)" class="space-y-4">
            <input type="hidden" id="editId">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">اسم الخدمة</label>
                <input type="text" id="editServiceName" required class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">رقم الواتساب</label>
                <input type="text" id="editWhatsappPhone" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">رقم الاتصال</label>
                <input type="text" id="editCallPhone" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">الترتيب</label>
                <input type="number" id="editSortOrder" min="0" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">حفظ</button>
                <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">إلغاء</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
let supportItems = [];

window.addEventListener('load', loadSupportContacts);

async function loadSupportContacts() {
    const data = await apiCall('/admin/api/support-contacts');
    const tbody = document.getElementById('supportTableBody');

    if (!data?.success || !data.data?.length) {
        supportItems = [];
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">لا توجد خدمات دعم بعد</td></tr>';
        return;
    }

    supportItems = data.data;
    tbody.innerHTML = supportItems.map(item => `
        <tr class="border-b hover:bg-gray-50">
            <td class="px-6 py-4 text-sm text-gray-500">${item.sort_order ?? 0}</td>
            <td class="px-6 py-4 font-semibold">${escapeHtml(item.service_name)}</td>
            <td class="px-6 py-4 font-mono text-sm">${item.whatsapp_phone ? escapeHtml(item.whatsapp_phone) : '<span class="text-gray-400">—</span>'}</td>
            <td class="px-6 py-4 font-mono text-sm">${item.call_phone ? escapeHtml(item.call_phone) : '<span class="text-gray-400">—</span>'}</td>
            <td class="px-6 py-4">
                <span class="px-3 py-1 rounded-full text-xs ${item.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                    ${item.is_active ? 'نشط' : 'غير نشط'}
                </span>
            </td>
            <td class="px-6 py-4 space-x-1 space-x-reverse">
                <button onclick="openEditModal(${item.id})" class="px-3 py-1 bg-gray-100 text-gray-700 rounded text-sm">تعديل</button>
                <button onclick="toggleSupport(${item.id}, ${item.is_active ? 'false' : 'true'})" class="px-3 py-1 bg-blue-100 text-blue-600 rounded text-sm">
                    ${item.is_active ? 'تعطيل' : 'تفعيل'}
                </button>
                <button onclick="deleteSupport(${item.id})" class="px-3 py-1 bg-red-100 text-red-600 rounded text-sm">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

async function saveSupport(event) {
    event.preventDefault();
    clearFieldErrors();
    try {
        const data = await apiCall('/admin/api/support-contacts', {
            method: 'POST',
            body: JSON.stringify({
                service_name: document.getElementById('serviceName').value.trim(),
                whatsapp_phone: document.getElementById('whatsappPhone').value.trim() || null,
                call_phone: document.getElementById('callPhone').value.trim() || null,
                sort_order: parseInt(document.getElementById('sortOrder').value || '0', 10),
                is_active: true,
            }),
        });

        if (data?.success) {
            showSuccess(data.message || 'تم الإضافة');
            document.getElementById('supportForm').reset();
            document.getElementById('sortOrder').value = '0';
            loadSupportContacts();
        } else {
            handleApiError(data);
        }
    } catch (err) {
        handleApiError(err);
    }
}

function openEditModal(id) {
    const item = supportItems.find(i => i.id === id);
    if (!item) return;
    document.getElementById('editId').value = item.id;
    document.getElementById('editServiceName').value = item.service_name || '';
    document.getElementById('editWhatsappPhone').value = item.whatsapp_phone || '';
    document.getElementById('editCallPhone').value = item.call_phone || '';
    document.getElementById('editSortOrder').value = item.sort_order ?? 0;
    const modal = document.getElementById('editModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

async function updateSupport(event) {
    event.preventDefault();
    const id = document.getElementById('editId').value;
    try {
        const data = await apiCall(`/admin/api/support-contacts/${id}`, {
            method: 'PUT',
            body: JSON.stringify({
                service_name: document.getElementById('editServiceName').value.trim(),
                whatsapp_phone: document.getElementById('editWhatsappPhone').value.trim() || null,
                call_phone: document.getElementById('editCallPhone').value.trim() || null,
                sort_order: parseInt(document.getElementById('editSortOrder').value || '0', 10),
            }),
        });

        if (data?.success) {
            showSuccess(data.message || 'تم التحديث');
            closeEditModal();
            loadSupportContacts();
        } else {
            handleApiError(data);
        }
    } catch (err) {
        handleApiError(err);
    }
}

async function toggleSupport(id, isActive) {
    const data = await apiCall(`/admin/api/support-contacts/${id}`, {
        method: 'PUT',
        body: JSON.stringify({ is_active: isActive }),
    });
    if (data?.success) loadSupportContacts();
}

async function deleteSupport(id) {
    if (!await confirmAction('هل أنت متأكد من حذف خدمة الدعم هذه؟')) return;
    const data = await apiCall(`/admin/api/support-contacts/${id}`, { method: 'DELETE' });
    if (data?.success) {
        showSuccess('تم الحذف');
        loadSupportContacts();
    }
}

function escapeHtml(text) {
    return String(text ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
</script>
@endsection
