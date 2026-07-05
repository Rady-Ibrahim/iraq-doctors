@extends('laboratory.layout')

@section('title', 'لوحة تحكم المعمل')
@section('page-title')
مرحباً، {{ $laboratory->name }}
@endsection
@section('page-description', 'نظرة عامة على الطلبات والتحاليل والإيرادات')

@section('content')
<div id="dashboardLoading" class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">
    <i class="fas fa-spinner fa-spin ml-2"></i> جاري تحميل لوحة التحكم...
</div>

<div id="dashboardContent" class="hidden space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4" id="kpiCards"></div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b flex flex-wrap justify-between items-center gap-3">
                <h3 class="font-semibold text-gray-800"><i class="fas fa-clipboard-list text-indigo-600 ml-2"></i>طلبات تحتاج متابعة</h3>
                <a href="{{ route('laboratory.orders.index') }}" class="text-sm text-indigo-600 hover:underline">كل الطلبات</a>
            </div>
            <div id="recentOrders"></div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-chart-pie text-indigo-600 ml-2"></i>توزيع الطلبات</h3>
                <div id="statusBars" class="space-y-3"></div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold text-gray-800"><i class="fas fa-vial text-amber-500 ml-2"></i>تنبيهات الكتالوج</h3>
                    <a href="{{ route('laboratory.tests.index') }}" class="text-xs text-indigo-600 hover:underline">إدارة التحاليل</a>
                </div>
                <div id="catalogAlerts"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4" id="quickLinks"></div>
</div>
@endsection

@section('scripts')
@include('partials.provider-dashboard-helpers')
<script>
const BASE = '/laboratory/dashboard';

window.addEventListener('load', loadDashboard);

async function loadDashboard() {
    const data = await apiCall('/laboratory/api/metrics');
    const loading = document.getElementById('dashboardLoading');
    const content = document.getElementById('dashboardContent');

    if (!data?.success) {
        loading.innerHTML = '<p class="text-red-600">تعذر تحميل البيانات</p>';
        return;
    }

    const m = data.data;
    loading.classList.add('hidden');
    content.classList.remove('hidden');

    document.getElementById('kpiCards').innerHTML = [
        renderKpiCard({
            label: 'طلبات بانتظار المراجعة',
            value: m.pending_orders,
            sub: 'جديدة + قيد المراجعة',
            icon: 'fa-inbox',
            color: 'blue',
            href: `${BASE}/orders?status=new`,
        }),
        renderKpiCard({
            label: 'بانتظار موافقة المريض',
            value: m.awaiting_patient,
            sub: 'تم عرض السعر',
            icon: 'fa-clock',
            color: 'purple',
            href: `${BASE}/orders?status=quoted`,
        }),
        renderKpiCard({
            label: 'قيد التنفيذ',
            value: m.in_progress_orders,
            sub: 'بعد موافقة المريض',
            icon: 'fa-flask',
            color: 'orange',
            href: `${BASE}/orders`,
        }),
        renderKpiCard({
            label: 'إيرادات الشهر',
            value: formatCurrency(m.revenue_this_month),
            sub: `${m.delivered_this_month} تحليل مُسلّم`,
            icon: 'fa-coins',
            color: 'indigo',
            href: `${BASE}/reports`,
        }),
    ].join('');

    document.getElementById('recentOrders').innerHTML = renderRecentOrdersTable(m.recent_orders, BASE);
    document.getElementById('statusBars').innerHTML = renderStatusBars(m.orders_by_status, 'indigo');
    document.getElementById('catalogAlerts').innerHTML = renderCatalogAlerts(m.catalog_alerts);

    document.getElementById('quickLinks').innerHTML = `
        <a href="${BASE}/orders" class="bg-white rounded-xl shadow-sm p-4 hover:shadow-md transition flex items-center gap-3">
            <i class="fas fa-clipboard-list text-indigo-600"></i><span class="text-sm font-semibold">الطلبات</span>
        </a>
        <a href="${BASE}/tests" class="bg-white rounded-xl shadow-sm p-4 hover:shadow-md transition flex items-center gap-3">
            <i class="fas fa-vial text-indigo-600"></i><span class="text-sm font-semibold">التحاليل</span>
        </a>
        <a href="${BASE}/reports" class="bg-white rounded-xl shadow-sm p-4 hover:shadow-md transition flex items-center gap-3">
            <i class="fas fa-chart-bar text-indigo-600"></i><span class="text-sm font-semibold">التقارير</span>
        </a>
        <a href="${BASE}/settings" class="bg-white rounded-xl shadow-sm p-4 hover:shadow-md transition flex items-center gap-3">
            <i class="fas fa-cog text-indigo-600"></i><span class="text-sm font-semibold">الإعدادات</span>
        </a>
    `;
}

function renderCatalogAlerts(alerts) {
    if (!alerts?.unavailable_count) {
        return '<p class="text-sm text-green-700 bg-green-50 rounded-lg p-3"><i class="fas fa-check-circle ml-1"></i>كل التحاليل المتاحة مفعّلة</p>';
    }

    let html = `<p class="text-sm text-amber-800 bg-amber-50 rounded-lg p-3 mb-3">
        <strong>${alerts.unavailable_count}</strong> تحليل غير متاح للطلب حالياً
    </p><ul class="space-y-2 text-sm max-h-48 overflow-y-auto">`;

    (alerts.unavailable_tests || []).forEach(item => {
        html += `<li class="flex justify-between gap-2 border-b pb-2">
            <span class="truncate">${item.name}</span>
            <span class="shrink-0 text-red-600 font-semibold">غير متاح</span>
        </li>`;
    });

    return html + '</ul>';
}
</script>
@endsection
