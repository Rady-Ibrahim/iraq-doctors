@extends('pharmacy.layout')

@section('title', 'التقارير والسجل')
@section('page-title', 'التقارير والسجل')
@section('page-description', 'إحصائيات الطلبات والمخزون وسجل الطلبات المكتملة')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 mb-6" id="metricsCards">
    <div class="bg-white rounded-xl shadow-sm p-6 col-span-full"><p class="text-sm text-gray-500">جاري التحميل...</p></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-chart-bar text-emerald-600 ml-2"></i>توزيع الطلبات حسب الحالة</h3>
        <div id="statusBreakdown" class="space-y-3">جاري التحميل...</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-receipt text-emerald-600 ml-2"></i>ملخص مالي</h3>
        <div id="quickSummary" class="space-y-4 text-sm text-gray-700">جاري التحميل...</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-8" id="stockAlertsSection">
    <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-boxes-stacked text-amber-500 ml-2"></i>تنبيهات المخزون</h3>
    <div id="stockAlerts">جاري التحميل...</div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b flex flex-wrap gap-4 justify-between items-center">
        <h3 class="font-semibold text-gray-800"><i class="fas fa-history text-emerald-600 ml-2"></i>سجل الطلبات المكتملة</h3>
        <div class="flex flex-wrap gap-2 items-center">
            <input type="text" id="historySearch" placeholder="بحث برقم الطلب أو المريض..." oninput="loadHistory()"
                class="px-3 py-2 border rounded-lg text-sm min-w-[220px]">
            <button type="button" onclick="downloadPdf()" class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 text-sm">
                <i class="fas fa-file-pdf ml-1"></i>تحميل PDF
            </button>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-right text-sm font-semibold">رقم الطلب</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">المريض</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">التنفيذ</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">الأدوية</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">المبلغ</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">تاريخ الإكمال</th>
                </tr>
            </thead>
            <tbody id="historyBody">
                <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">جاري التحميل...</td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
@include('partials.provider-dashboard-helpers')
<script>
window.addEventListener('load', () => {
    loadMetrics();
    loadHistory();
});

async function loadMetrics() {
    const data = await apiCall('/pharmacy/api/metrics');
    if (!data?.success) return;
    const m = data.data;

    document.getElementById('metricsCards').innerHTML = [
        renderKpiCard({ label: 'طلبات نشطة', value: m.active_orders, icon: 'fa-spinner', color: 'blue' }),
        renderKpiCard({ label: 'مكتملة', value: m.completed_total, icon: 'fa-check-circle', color: 'green' }),
        renderKpiCard({ label: 'مكتملة هذا الشهر', value: m.completed_this_month, icon: 'fa-calendar', color: 'teal' }),
        renderKpiCard({ label: 'توصيل نشط', value: m.delivery_orders, icon: 'fa-truck', color: 'cyan' }),
        renderKpiCard({ label: 'إيرادات الشهر', value: formatCurrency(m.revenue_this_month), icon: 'fa-coins', color: 'emerald' }),
    ].join('');

    document.getElementById('statusBreakdown').innerHTML = renderStatusBars(m.orders_by_status, 'emerald');

    document.getElementById('quickSummary').innerHTML = `
        <div class="flex justify-between border-b pb-3">
            <span class="text-gray-500">إجمالي الإيرادات</span>
            <strong class="text-emerald-600">${formatCurrency(m.revenue_total)}</strong>
        </div>
        <div class="flex justify-between border-b pb-3">
            <span class="text-gray-500">إيرادات الشهر</span>
            <strong>${formatCurrency(m.revenue_this_month)}</strong>
        </div>
        <div class="flex justify-between border-b pb-3">
            <span class="text-gray-500">أدوية في الكتالوج</span>
            <strong>${m.catalog_medicines}</strong>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-500">بانتظار موافقة المريض</span>
            <strong class="text-purple-600">${m.awaiting_patient}</strong>
        </div>
    `;

    document.getElementById('stockAlerts').innerHTML = renderPharmacyStockAlerts(m.stock_alerts);
}

function renderPharmacyStockAlerts(alerts) {
    if (!alerts) return '<p class="text-gray-500 text-sm">—</p>';
    const total = alerts.low_stock_count + alerts.out_of_stock_count;
    if (!total) {
        return '<p class="text-green-700 bg-green-50 rounded-lg p-4 text-sm"><i class="fas fa-check ml-1"></i>لا توجد تنبيهات مخزون حالياً</p>';
    }
    const rows = [...(alerts.out_of_stock || []), ...(alerts.low_stock || [])];
    return `
        <p class="text-sm text-amber-800 mb-4">${total} تنبيه — ${alerts.out_of_stock_count} نفد، ${alerts.low_stock_count} مخزون منخفض</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            ${rows.slice(0, 10).map(item => `
                <div class="flex justify-between items-center border rounded-lg p-3 text-sm ${item.stock_quantity <= 0 ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50'}">
                    <span>${item.name}</span>
                    <span class="font-bold ${item.stock_quantity <= 0 ? 'text-red-600' : 'text-amber-700'}">${item.stock_quantity <= 0 ? 'نفد' : item.stock_quantity + ' متبقي'}</span>
                </div>
            `).join('')}
        </div>
        <a href="/pharmacy/dashboard/medicines" class="inline-block mt-4 text-sm text-emerald-600 hover:underline">تحديث المخزون ←</a>
    `;
}

async function loadHistory() {
    const params = new URLSearchParams({ limit: 100 });
    const search = document.getElementById('historySearch').value;
    if (search) params.set('search', search);

    const data = await apiCall(`/pharmacy/api/reports/history?${params}`);
    const tbody = document.getElementById('historyBody');
    if (!data?.success || !data.data?.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">لا توجد طلبات مكتملة في السجل</td></tr>';
        return;
    }

    tbody.innerHTML = data.data.map(row => `
        <tr class="border-b hover:bg-gray-50">
            <td class="px-6 py-4 font-mono text-sm">
                <a href="/pharmacy/dashboard/orders/${row.id}" class="text-emerald-600 hover:underline">${row.order_number}</a>
            </td>
            <td class="px-6 py-4">${row.patient_name || '—'}</td>
            <td class="px-6 py-4 text-sm">
                ${row.fulfillment_type === 'delivery'
                    ? '<span class="text-blue-700"><i class="fas fa-truck ml-1"></i>' + (row.fulfillment_label || 'توصيل') + '</span>'
                    : '<span class="text-gray-700"><i class="fas fa-store ml-1"></i>' + (row.fulfillment_label || 'استلام') + '</span>'}
            </td>
            <td class="px-6 py-4 text-sm text-gray-600">${row.items_count}</td>
            <td class="px-6 py-4 font-semibold">${formatCurrency(row.total_amount || 0)}</td>
            <td class="px-6 py-4 text-sm">${row.completed_at || '—'}</td>
        </tr>
    `).join('');
}

function downloadPdf() {
    const params = new URLSearchParams({ limit: 100 });
    const search = document.getElementById('historySearch').value;
    if (search) params.set('search', search);
    window.location.href = `{{ route('pharmacy.reports.pdf') }}?${params}`;
}
</script>
@endsection
