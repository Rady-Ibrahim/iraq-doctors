@extends('laboratory.layout')

@section('title', 'التقارير والسجل')
@section('page-title', 'التقارير والسجل')
@section('page-description', 'إحصائيات الطلبات والكتالوج وسجل التحاليل المكتملة')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 mb-6" id="metricsCards">
    <div class="bg-white rounded-xl shadow-sm p-6 col-span-full"><p class="text-sm text-gray-500">جاري التحميل...</p></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-chart-bar text-indigo-600 ml-2"></i>توزيع الطلبات حسب الحالة</h3>
        <div id="statusBreakdown" class="space-y-3">جاري التحميل...</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-receipt text-indigo-600 ml-2"></i>ملخص مالي</h3>
        <div id="quickSummary" class="space-y-4 text-sm text-gray-700">جاري التحميل...</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-8">
    <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-vial text-amber-500 ml-2"></i>تحاليل غير متاحة في الكتالوج</h3>
    <div id="catalogAlerts">جاري التحميل...</div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b flex flex-wrap gap-4 justify-between items-center">
        <h3 class="font-semibold text-gray-800"><i class="fas fa-history text-indigo-600 ml-2"></i>سجل التحاليل المكتملة</h3>
        <input type="text" id="historySearch" placeholder="بحث برقم الطلب أو المريض..." oninput="loadHistory()"
            class="px-3 py-2 border rounded-lg text-sm min-w-[220px]">
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-right text-sm font-semibold">رقم الطلب</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">المريض</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">التحاليل</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">المبلغ</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">تاريخ الإكمال</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">النتائج</th>
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
    const data = await apiCall('/laboratory/api/metrics');
    if (!data?.success) return;
    const m = data.data;

    document.getElementById('metricsCards').innerHTML = [
        renderKpiCard({ label: 'طلبات نشطة', value: m.active_orders, icon: 'fa-spinner', color: 'blue' }),
        renderKpiCard({ label: 'مُسلّمة', value: m.delivered_total, icon: 'fa-check-circle', color: 'green' }),
        renderKpiCard({ label: 'مُسلّمة هذا الشهر', value: m.delivered_this_month, icon: 'fa-calendar', color: 'teal' }),
        renderKpiCard({ label: 'قيد التنفيذ', value: m.in_progress_orders, icon: 'fa-flask', color: 'orange' }),
        renderKpiCard({ label: 'إيرادات الشهر', value: formatCurrency(m.revenue_this_month), icon: 'fa-coins', color: 'indigo' }),
    ].join('');

    document.getElementById('statusBreakdown').innerHTML = renderStatusBars(m.orders_by_status, 'indigo');

    document.getElementById('quickSummary').innerHTML = `
        <div class="flex justify-between border-b pb-3">
            <span class="text-gray-500">إجمالي الإيرادات</span>
            <strong class="text-indigo-600">${formatCurrency(m.revenue_total)}</strong>
        </div>
        <div class="flex justify-between border-b pb-3">
            <span class="text-gray-500">إيرادات الشهر</span>
            <strong>${formatCurrency(m.revenue_this_month)}</strong>
        </div>
        <div class="flex justify-between border-b pb-3">
            <span class="text-gray-500">تحاليل متاحة</span>
            <strong>${m.catalog_tests}</strong>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-500">بانتظار موافقة المريض</span>
            <strong class="text-purple-600">${m.awaiting_patient}</strong>
        </div>
    `;

    const alerts = m.catalog_alerts;
    const el = document.getElementById('catalogAlerts');
    if (!alerts?.unavailable_count) {
        el.innerHTML = '<p class="text-green-700 bg-green-50 rounded-lg p-4 text-sm"><i class="fas fa-check ml-1"></i>كل التحاليل مفعّلة للطلب</p>';
    } else {
        el.innerHTML = `
            <p class="text-sm text-amber-800 mb-3">${alerts.unavailable_count} تحليل غير متاح</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                ${(alerts.unavailable_tests || []).map(t => `
                    <div class="border border-amber-200 bg-amber-50 rounded-lg p-3 text-sm flex justify-between">
                        <span>${t.name}</span><span class="text-red-600 font-semibold">غير متاح</span>
                    </div>
                `).join('')}
            </div>
            <a href="/laboratory/dashboard/tests" class="inline-block mt-4 text-sm text-indigo-600 hover:underline">إدارة التحاليل ←</a>
        `;
    }
}

async function loadHistory() {
    const params = new URLSearchParams({ limit: 100 });
    const search = document.getElementById('historySearch').value;
    if (search) params.set('search', search);

    const data = await apiCall(`/laboratory/api/reports/history?${params}`);
    const tbody = document.getElementById('historyBody');
    if (!data?.success || !data.data?.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">لا توجد طلبات مكتملة في السجل</td></tr>';
        return;
    }

    tbody.innerHTML = data.data.map(row => `
        <tr class="border-b hover:bg-gray-50">
            <td class="px-6 py-4 font-mono text-sm">
                <a href="/laboratory/dashboard/orders/${row.id}" class="text-indigo-600 hover:underline">${row.order_number}</a>
            </td>
            <td class="px-6 py-4">${row.patient_name || '—'}</td>
            <td class="px-6 py-4 text-sm text-gray-600">${(row.tests || []).slice(0, 2).join('، ')}${row.tests?.length > 2 ? '...' : ''}</td>
            <td class="px-6 py-4 font-semibold">${formatCurrency(row.total_amount || 0)}</td>
            <td class="px-6 py-4 text-sm">${row.completed_at || '—'}</td>
            <td class="px-6 py-4 text-sm">${row.results_count} ملف</td>
        </tr>
    `).join('');
}
</script>
@endsection
