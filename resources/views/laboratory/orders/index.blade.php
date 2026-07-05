@extends('laboratory.layout')

@section('title', 'طلبات التحاليل')
@section('page-title', 'طلبات التحاليل')
@section('page-description', 'إدارة طلبات المرضى — مراجعة الروشتة وعرض السعر ومتابعة الحالة')

@section('content')
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6" id="orderStats"></div>

<div class="mb-6 flex flex-wrap gap-3 items-center">
    <button onclick="filterStatus('new')" data-filter="new" class="filter-btn px-4 py-2 rounded-lg bg-indigo-100 text-indigo-800 font-semibold text-sm">جديدة <span class="count-badge hidden ml-1 bg-indigo-600 text-white text-xs px-1.5 rounded-full"></span></button>
    <button onclick="filterStatus('reviewing')" data-filter="reviewing" class="filter-btn px-4 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold text-sm">قيد المراجعة <span class="count-badge hidden ml-1 bg-yellow-500 text-white text-xs px-1.5 rounded-full"></span></button>
    <button onclick="filterStatus('quoted')" data-filter="quoted" class="filter-btn px-4 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold text-sm">معروض السعر <span class="count-badge hidden ml-1 bg-purple-500 text-white text-xs px-1.5 rounded-full"></span></button>
    <button onclick="filterStatus('accepted')" data-filter="accepted" class="filter-btn px-4 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold text-sm">مقبول <span class="count-badge hidden ml-1 bg-teal-500 text-white text-xs px-1.5 rounded-full"></span></button>
    <button onclick="filterStatus('processing')" data-filter="processing" class="filter-btn px-4 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold text-sm">قيد التحليل</button>
    <button onclick="filterStatus('')" data-filter="" class="filter-btn px-4 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold text-sm">الكل</button>
    <input type="text" id="searchInput" placeholder="بحث برقم الطلب أو اسم المريض..." oninput="loadOrders()"
        class="mr-auto px-3 py-2 border rounded-lg text-sm min-w-[220px]">
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">رقم الطلب</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">المريض</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">النوع</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">التحاليل</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">المبلغ</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الحالة</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">إجراءات</th>
                </tr>
            </thead>
            <tbody id="ordersBody">
                <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">جاري التحميل...</td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
@include('partials.provider-dashboard-helpers')
<script>
let currentStatus = 'new';
let statusCounts = {};

window.addEventListener('load', () => {
    const params = new URLSearchParams(window.location.search);
    const status = params.get('status');
    filterStatus(status !== null ? status : 'new', false);
    loadOrderStats();
    loadOrders();
});

function filterStatus(status, reload = true) {
    currentStatus = status;
    document.querySelectorAll('.filter-btn').forEach(btn => {
        const active = btn.dataset.filter === status;
        btn.className = active
            ? 'filter-btn px-4 py-2 rounded-lg bg-indigo-100 text-indigo-800 font-semibold text-sm'
            : 'filter-btn px-4 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold text-sm';
    });
    if (reload) loadOrders();
}

async function loadOrderStats() {
    const data = await apiCall('/laboratory/api/metrics');
    if (!data?.success) return;

    const m = data.data;
    statusCounts = Object.fromEntries((m.orders_by_status || []).map(s => [s.status, s.count]));

    document.getElementById('orderStats').innerHTML = `
        <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
            <p class="text-xs text-blue-700">بانتظار المراجعة</p>
            <p class="text-xl font-bold text-blue-800">${m.pending_orders}</p>
        </div>
        <div class="bg-purple-50 border border-purple-100 rounded-xl p-4">
            <p class="text-xs text-purple-700">بانتظار المريض</p>
            <p class="text-xl font-bold text-purple-800">${m.awaiting_patient}</p>
        </div>
        <div class="bg-orange-50 border border-orange-100 rounded-xl p-4">
            <p class="text-xs text-orange-700">قيد التنفيذ</p>
            <p class="text-xl font-bold text-orange-800">${m.in_progress_orders}</p>
        </div>
        <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4">
            <p class="text-xs text-indigo-700">نشطة إجمالاً</p>
            <p class="text-xl font-bold text-indigo-800">${m.active_orders}</p>
        </div>
    `;

    document.querySelectorAll('.filter-btn').forEach(btn => {
        const badge = btn.querySelector('.count-badge');
        const count = statusCounts[btn.dataset.filter];
        if (badge && count > 0) {
            badge.textContent = count;
            badge.classList.remove('hidden');
        } else if (badge) {
            badge.classList.add('hidden');
        }
    });
}

async function loadOrders() {
    const params = new URLSearchParams({ limit: 50 });
    if (currentStatus) params.set('status', currentStatus);
    const search = document.getElementById('searchInput').value;
    if (search) params.set('search', search);

    const data = await apiCall(`/laboratory/api/orders?${params}`);
    const tbody = document.getElementById('ordersBody');
    if (!data?.success || !data.data?.orders?.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">لا توجد طلبات</td></tr>';
        return;
    }

    tbody.innerHTML = data.data.orders.map(o => `
        <tr class="border-b hover:bg-gray-50">
            <td class="px-6 py-4 font-mono text-sm font-semibold">${o.order_number}</td>
            <td class="px-6 py-4">
                <p class="font-semibold">${o.patient_name || '-'}</p>
                <p class="text-xs text-gray-500">${o.patient_phone || ''}</p>
            </td>
            <td class="px-6 py-4 text-sm">
                ${o.has_prescription_image || o.source === 'prescription'
                    ? '<span class="text-amber-700"><i class="fas fa-image ml-1"></i>روشتة</span>'
                    : '<span class="text-indigo-700"><i class="fas fa-list ml-1"></i>كتالوج</span>'}
            </td>
            <td class="px-6 py-4 text-sm">${o.items_count}</td>
            <td class="px-6 py-4 text-sm font-semibold">${o.total_amount ? formatCurrency(o.total_amount) : '—'}</td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 rounded-full text-xs ${providerStatusClass(o.status)}">${o.status_label}</span>
                ${o.status === 'quoted' ? '<p class="text-xs text-purple-600 mt-1">بانتظار المريض</p>' : ''}
            </td>
            <td class="px-6 py-4">
                <a href="/laboratory/dashboard/orders/${o.id}" class="px-3 py-1 bg-indigo-600 text-white text-xs rounded-lg hover:bg-indigo-700">عرض</a>
            </td>
        </tr>
    `).join('');
}
</script>
@endsection
