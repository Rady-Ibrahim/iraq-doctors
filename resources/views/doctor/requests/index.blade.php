@extends('doctor.layout')

@section('title', 'طلبات المواعيد')
@section('page-title', 'طلبات المواعيد')
@section('page-description', 'إدارة طلبات الحجز من المرضى')

@section('content')
<div class="mb-6 flex gap-3 flex-wrap">
    <button onclick="filterStatus('pending')" id="filter-pending" class="px-4 py-2 rounded-lg bg-yellow-100 text-yellow-800 font-semibold text-sm">معلقة</button>
    <button onclick="filterStatus('confirmed')" id="filter-confirmed" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold text-sm">مؤكدة</button>
    <button onclick="filterStatus('completed')" id="filter-completed" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold text-sm">مكتملة</button>
    <button onclick="filterStatus('')" id="filter-all" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold text-sm">الكل</button>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">المريض</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">التاريخ</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الوقت</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الحالة</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">إجراءات</th>
            </tr>
        </thead>
        <tbody id="requestsTable">
            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">جاري التحميل...</td></tr>
        </tbody>
    </table>
</div>
@endsection

@section('scripts')
<script>
let currentStatus = 'pending';

window.addEventListener('load', () => {
    filterStatus('pending');
    loadPendingBadge();
});

function filterStatus(status) {
    currentStatus = status;
    document.querySelectorAll('[id^="filter-"]').forEach(btn => {
        btn.className = 'px-4 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold text-sm';
    });
    const activeId = status ? `filter-${status}` : 'filter-all';
    const activeBtn = document.getElementById(activeId);
    if (activeBtn) activeBtn.className = 'px-4 py-2 rounded-lg bg-teal-100 text-teal-800 font-semibold text-sm';
    loadRequests();
}

async function loadRequests() {
    try {
        showLoading();
        const params = new URLSearchParams({ limit: 50 });
        if (currentStatus) params.set('status', currentStatus);
        const data = await apiCall(`/doctor/api/appointments?${params}`);
        const tbody = document.getElementById('requestsTable');

        if (!data?.success || !data.data?.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">لا توجد طلبات</td></tr>';
            return;
        }

        tbody.innerHTML = data.data.map(apt => `
            <tr class="border-b hover:bg-gray-50">
                <td class="px-6 py-4">
                    <p class="font-semibold">${apt.patient_name || '-'}</p>
                    <p class="text-xs text-gray-500">${apt.patient_phone || ''}</p>
                </td>
                <td class="px-6 py-4">${apt.date || '-'}</td>
                <td class="px-6 py-4">${apt.time || '-'}</td>
                <td class="px-6 py-4"><span class="px-2 py-1 rounded-full text-xs ${statusClass(apt.status)}">${statusText(apt.status)}</span></td>
                <td class="px-6 py-4">
                    <div class="flex flex-wrap gap-2">
                        ${apt.status === 'pending' ? `
                            <button onclick="confirmAppointment(${apt.id})" class="px-3 py-1 bg-green-600 text-white text-xs rounded-lg">قبول</button>
                            <button onclick="rejectAppointment(${apt.id})" class="px-3 py-1 bg-red-600 text-white text-xs rounded-lg">رفض</button>
                        ` : ''}
                        ${apt.status === 'confirmed' ? `
                            <button onclick="completeAppointment(${apt.id})" class="px-3 py-1 bg-blue-600 text-white text-xs rounded-lg">إكمال</button>
                        ` : ''}
                        ${apt.can_add_record ? `
                            <a href="${apt.record_create_url}" class="px-3 py-1 bg-teal-600 text-white text-xs rounded-lg inline-block">إضافة سجل طبي</a>
                        ` : apt.has_medical_record ? '<span class="text-xs text-green-600">✓ سجل مضاف</span>' : ''}
                    </div>
                </td>
            </tr>
        `).join('');
    } catch (e) {
        console.error(e);
    } finally {
        hideLoading();
    }
}

async function confirmAppointment(id) {
    if (!confirm('تأكيد هذا الموعد؟')) return;
    const data = await apiPost(`/doctor/api/appointments/${id}/confirm`, {});
    if (data?.success) { alert(data.message); loadRequests(); loadPendingBadge(); }
    else alert(data?.error?.message || 'حدث خطأ');
}

async function rejectAppointment(id) {
    if (!confirm('رفض هذا الموعد؟')) return;
    const data = await apiPost(`/doctor/api/appointments/${id}/reject`, {});
    if (data?.success) { alert(data.message); loadRequests(); loadPendingBadge(); }
    else alert(data?.error?.message || 'حدث خطأ');
}

async function completeAppointment(id) {
    if (!confirm('تحديد الموعد كمكتمل؟')) return;
    const data = await apiPost(`/doctor/api/appointments/${id}/complete`, {});
    if (data?.success) {
        alert(data.message);
        if (data.data?.can_add_record && confirm('هل تريد إضافة سجل طبي الآن؟')) {
            window.location.href = data.data.record_create_url;
            return;
        }
        loadRequests();
    } else alert(data?.error?.message || 'حدث خطأ');
}

async function loadPendingBadge() {
    const data = await apiCall('/doctor/api/metrics');
    const badge = document.getElementById('pendingRequestsBadge');
    if (badge && data?.success) {
        const count = data.data?.appointments?.pending_requests || 0;
        badge.textContent = count;
        badge.classList.toggle('hidden', count === 0);
    }
}

function statusClass(s) {
    return ({ pending: 'bg-yellow-100 text-yellow-800', confirmed: 'bg-blue-100 text-blue-800', completed: 'bg-green-100 text-green-800', cancelled: 'bg-red-100 text-red-800' })[s] || 'bg-gray-100';
}
function statusText(s) {
    return ({ pending: 'معلق', confirmed: 'مؤكد', completed: 'مكتمل', cancelled: 'ملغي' })[s] || s;
}
</script>
@endsection
