@extends('admin.layout')

@section('title', 'إدارة الطلبات')
@section('page-title', 'الطلبات')
@section('page-description', 'متابعة طلبات التحاليل والأدوية')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="flex flex-wrap gap-2 mb-4">
        <button id="tabLab" onclick="switchTab('laboratory')" class="px-4 py-2 rounded-lg bg-blue-600 text-white">طلبات المعامل</button>
        <button id="tabPharmacy" onclick="switchTab('pharmacy')" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700">طلبات الصيدليات</button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <input type="text" id="searchInput" placeholder="بحث برقم الطلب أو المريض..." class="w-full px-4 py-2 border rounded-lg">
        <select id="statusFilter" class="w-full px-4 py-2 border rounded-lg">
            <option value="">جميع الحالات</option>
        </select>
        <input type="date" id="dateFromFilter" class="w-full px-4 py-2 border rounded-lg">
        <input type="date" id="dateToFilter" class="w-full px-4 py-2 border rounded-lg">
        <button onclick="loadOrders()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">بحث</button>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b">
            <tr id="tableHead">
                <th class="px-6 py-3 text-right text-sm font-semibold">رقم الطلب</th>
                <th class="px-6 py-3 text-right text-sm font-semibold">المريض</th>
                <th class="px-6 py-3 text-right text-sm font-semibold provider-col">المعمل</th>
                <th class="px-6 py-3 text-right text-sm font-semibold">الحالة</th>
                <th class="px-6 py-3 text-right text-sm font-semibold">المبلغ</th>
                <th class="px-6 py-3 text-right text-sm font-semibold">التاريخ</th>
                <th class="px-6 py-3 text-right text-sm font-semibold">الإجراء</th>
            </tr>
        </thead>
        <tbody id="ordersTableBody">
            <tr><td colspan="7" class="py-8 text-center text-gray-500">جاري التحميل...</td></tr>
        </tbody>
    </table>
</div>

<div id="orderModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold" id="modalTitle">تفاصيل الطلب</h3>
            <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></button>
        </div>
        <div id="modalContent" class="space-y-3 text-sm"></div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let currentTab = 'laboratory';
let currentPage = 1;

const labStatuses = {
    '': 'جميع الحالات', new: 'جديد', reviewing: 'قيد المراجعة', quoted: 'عرض سعر',
    accepted: 'مقبول', scheduled: 'مجدول', collected: 'تم السحب', processing: 'قيد التحليل',
    ready: 'جاهز', delivered: 'تم التسليم', cancelled: 'ملغي'
};
const pharmacyStatuses = {
    '': 'جميع الحالات', new: 'جديد', reviewing: 'قيد المراجعة', quoted: 'عرض سعر',
    accepted: 'مقبول', preparing: 'قيد التجهيز', out_for_delivery: 'في الطريق',
    completed: 'مكتمل', cancelled: 'ملغي'
};

function switchTab(tab) {
    currentTab = tab;
    currentPage = 1;
    document.getElementById('tabLab').className = tab === 'laboratory'
        ? 'px-4 py-2 rounded-lg bg-blue-600 text-white'
        : 'px-4 py-2 rounded-lg bg-gray-100 text-gray-700';
    document.getElementById('tabPharmacy').className = tab === 'pharmacy'
        ? 'px-4 py-2 rounded-lg bg-blue-600 text-white'
        : 'px-4 py-2 rounded-lg bg-gray-100 text-gray-700';
    document.querySelector('.provider-col').textContent = tab === 'laboratory' ? 'المعمل' : 'الصيدلية';
    fillStatusFilter();
    loadOrders();
}

function fillStatusFilter() {
    const map = currentTab === 'laboratory' ? labStatuses : pharmacyStatuses;
    document.getElementById('statusFilter').innerHTML = Object.entries(map)
        .map(([v, l]) => `<option value="${v}">${l}</option>`).join('');
}

function statusBadge(label) {
    return `<span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100">${label}</span>`;
}

async function loadOrders(page = 1) {
    currentPage = page;
    const params = new URLSearchParams({ page, limit: 20 });
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const dateFrom = document.getElementById('dateFromFilter').value;
    const dateTo = document.getElementById('dateToFilter').value;
    if (search) params.set('search', search);
    if (status) params.set('status', status);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);

    const endpoint = currentTab === 'laboratory'
        ? `/admin/api/laboratory-orders?${params}`
        : `/admin/api/pharmacy-orders?${params}`;

    const data = await apiCall(endpoint);
    const tbody = document.getElementById('ordersTableBody');
    if (!data?.success || !data.data?.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-gray-500">لا توجد نتائج</td></tr>';
        return;
    }

    tbody.innerHTML = data.data.map(order => {
        const provider = currentTab === 'laboratory' ? order.laboratory_name : order.pharmacy_name;
        return `
        <tr class="border-b hover:bg-gray-50">
            <td class="px-6 py-4 font-mono text-sm">${order.order_number}</td>
            <td class="px-6 py-4">${order.patient_name || '-'}<br><span class="text-xs text-gray-500">${order.patient_phone || ''}</span></td>
            <td class="px-6 py-4">${provider || '-'}</td>
            <td class="px-6 py-4">${statusBadge(order.status_label)}</td>
            <td class="px-6 py-4">${order.total_amount ? formatCurrency(order.total_amount) : '-'}</td>
            <td class="px-6 py-4 text-sm">${order.created_at || '-'}</td>
            <td class="px-6 py-4">
                <button onclick="showOrder(${order.id})" class="text-blue-600 hover:underline">عرض</button>
            </td>
        </tr>`;
    }).join('');
}

async function showOrder(id) {
    const endpoint = currentTab === 'laboratory'
        ? `/admin/api/laboratory-orders/${id}`
        : `/admin/api/pharmacy-orders/${id}`;
    const data = await apiCall(endpoint);
    if (!data?.success) return;
    const o = data.data;

    let itemsHtml = '';
    if (o.items?.length) {
        itemsHtml = '<ul class="list-disc mr-5 mt-2">' + o.items.map(i =>
            `<li>${i.test_name || i.medicine_name} × ${i.quantity} — ${formatCurrency(i.price)}</li>`
        ).join('') + '</ul>';
    }

    document.getElementById('modalTitle').textContent = 'طلب ' + o.order_number;
    document.getElementById('modalContent').innerHTML = `
        <p><strong>المريض:</strong> ${o.patient_name} (${o.patient_phone || '-'})</p>
        <p><strong>${currentTab === 'laboratory' ? 'المعمل' : 'الصيدلية'}:</strong> ${o.laboratory_name || o.pharmacy_name || '-'}</p>
        <p><strong>الحالة:</strong> ${o.status_label}</p>
        <p><strong>المبلغ:</strong> ${o.total_amount ? formatCurrency(o.total_amount) : '-'}</p>
        ${o.fulfillment_type ? `<p><strong>الاستلام:</strong> ${o.fulfillment_type === 'delivery' ? 'توصيل' : 'استلام'}</p>` : ''}
        ${o.delivery_address ? `<p><strong>عنوان التوصيل:</strong> ${o.delivery_address}</p>` : ''}
        ${o.patient_notes ? `<p><strong>ملاحظات المريض:</strong> ${o.patient_notes}</p>` : ''}
        ${o.quote_notes ? `<p><strong>ملاحظات العرض:</strong> ${o.quote_notes}</p>` : ''}
        <p><strong>العناصر:</strong>${itemsHtml || ' لا يوجد'}</p>
    `;
    document.getElementById('orderModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('orderModal').classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', () => {
    fillStatusFilter();
    loadOrders();
});
</script>
@endsection
