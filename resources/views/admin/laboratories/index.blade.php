@extends('admin.layout')

@section('title', 'إدارة المعامل')
@section('page-title', 'المعامل')
@section('page-description', 'مراجعة طلبات تسجيل المعامل والموافقة عليها')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input type="text" id="searchInput" placeholder="ابحث عن معمل..." class="w-full px-4 py-2 border rounded-lg">
        <select id="statusFilter" class="w-full px-4 py-2 border rounded-lg">
            <option value="">جميع الحالات</option>
            <option value="pending">معلق</option>
            <option value="approved">موافق عليه</option>
            <option value="rejected">مرفوض</option>
            <option value="suspended">معلّق</option>
        </select>
        <button onclick="loadLaboratories()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">بحث</button>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-6 py-3 text-right text-sm font-semibold">المعمل</th>
                <th class="px-6 py-3 text-right text-sm font-semibold">المسؤول</th>
                <th class="px-6 py-3 text-right text-sm font-semibold">المحافظة</th>
                <th class="px-6 py-3 text-right text-sm font-semibold">الحالة</th>
                <th class="px-6 py-3 text-right text-sm font-semibold">الإجراء</th>
            </tr>
        </thead>
        <tbody id="labsTableBody">
            <tr><td colspan="5" class="py-8 text-center text-gray-500">جاري التحميل...</td></tr>
        </tbody>
    </table>
</div>
@endsection

@section('scripts')
<script>
let currentPage = 1;

function statusBadge(status) {
    const map = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
        suspended: 'bg-gray-100 text-gray-800',
    };
    const labels = { pending: 'معلق', approved: 'موافق', rejected: 'مرفوض', suspended: 'معلّق' };
    return `<span class="px-2 py-1 rounded-full text-xs font-semibold ${map[status] || ''}">${labels[status] || status}</span>`;
}

async function loadLaboratories(page = 1) {
    currentPage = page;
    const params = new URLSearchParams({ page, limit: 20 });
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    if (search) params.set('search', search);
    if (status) params.set('status', status);

    const data = await apiCall(`/admin/api/laboratories?${params}`);
    const tbody = document.getElementById('labsTableBody');
    if (!data?.success || !data.data?.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-gray-500">لا توجد نتائج</td></tr>';
        return;
    }

    tbody.innerHTML = data.data.map(lab => `
        <tr class="border-b hover:bg-gray-50">
            <td class="px-6 py-4 font-semibold">${lab.name || '-'}</td>
            <td class="px-6 py-4">${lab.owner_name || '-'}</td>
            <td class="px-6 py-4">${lab.governorate || '-'}</td>
            <td class="px-6 py-4">${statusBadge(lab.status)}</td>
            <td class="px-6 py-4">
                <a href="/admin/dashboard/laboratories/${lab.id}" class="text-blue-600 hover:underline">عرض</a>
            </td>
        </tr>
    `).join('');
}

document.addEventListener('DOMContentLoaded', () => loadLaboratories());
</script>
@endsection
