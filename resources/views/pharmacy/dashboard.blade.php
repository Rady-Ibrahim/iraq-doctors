@extends('pharmacy.layout')

@section('title', 'لوحة تحكم الصيدلية')
@section('page-title')
مرحباً، {{ $pharmacy->name }}
@endsection
@section('page-description', 'نظرة عامة على الطلبات والمخزون والإيرادات')

@section('content')
<div id="dashboardLoading" class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">
    <i class="fas fa-spinner fa-spin ml-2"></i> جاري تحميل لوحة التحكم...
</div>

<div id="dashboardContent" class="hidden space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4" id="kpiCards"></div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b flex flex-wrap justify-between items-center gap-3">
                <h3 class="font-semibold text-gray-800"><i class="fas fa-clipboard-list text-emerald-600 ml-2"></i>طلبات تحتاج متابعة</h3>
                <a href="{{ route('pharmacy.orders.index') }}" class="text-sm text-emerald-600 hover:underline">كل الطلبات</a>
            </div>
            <div id="recentOrders"></div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-chart-pie text-emerald-600 ml-2"></i>توزيع الطلبات</h3>
                <div id="statusBars" class="space-y-3"></div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold text-gray-800"><i class="fas fa-exclamation-triangle text-amber-500 ml-2"></i>تنبيهات المخزون</h3>
                    <a href="{{ route('pharmacy.medicines.index') }}" class="text-xs text-emerald-600 hover:underline">إدارة الأدوية</a>
                </div>
                <div id="stockAlerts"></div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-calendar-times text-red-500 ml-2"></i>انتهاء الصلاحية</h3>
                <div id="expiryAlerts"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4" id="quickLinks"></div>
</div>
@endsection

@section('scripts')
@include('partials.provider-dashboard-helpers')
<script>
const BASE = '/pharmacy/dashboard';

window.addEventListener('load', loadDashboard);

async function loadDashboard() {
    const data = await apiCall('/pharmacy/api/metrics');
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
            sub: `${m.delivery_orders} طلب توصيل نشط`,
            icon: 'fa-truck',
            color: 'orange',
            href: `${BASE}/orders`,
        }),
        renderKpiCard({
            label: 'إيرادات الشهر',
            value: formatCurrency(m.revenue_this_month),
            sub: `${m.completed_this_month} طلب مكتمل`,
            icon: 'fa-coins',
            color: 'emerald',
            href: `${BASE}/reports`,
        }),
    ].join('');

    document.getElementById('recentOrders').innerHTML = renderRecentOrdersTable(m.recent_orders, BASE);
    document.getElementById('statusBars').innerHTML = renderStatusBars(m.orders_by_status, 'emerald');
    document.getElementById('stockAlerts').innerHTML = renderStockAlerts(m.stock_alerts);
    document.getElementById('expiryAlerts').innerHTML = renderExpiryAlerts(m.expiry_alerts);

    document.getElementById('quickLinks').innerHTML = `
        <a href="${BASE}/orders" class="bg-white rounded-xl shadow-sm p-4 hover:shadow-md transition flex items-center gap-3">
            <i class="fas fa-clipboard-list text-emerald-600"></i><span class="text-sm font-semibold">الطلبات</span>
        </a>
        <a href="${BASE}/medicines" class="bg-white rounded-xl shadow-sm p-4 hover:shadow-md transition flex items-center gap-3">
            <i class="fas fa-capsules text-emerald-600"></i><span class="text-sm font-semibold">الأدوية</span>
        </a>
        <a href="${BASE}/reports" class="bg-white rounded-xl shadow-sm p-4 hover:shadow-md transition flex items-center gap-3">
            <i class="fas fa-chart-bar text-emerald-600"></i><span class="text-sm font-semibold">التقارير</span>
        </a>
        <a href="${BASE}/settings" class="bg-white rounded-xl shadow-sm p-4 hover:shadow-md transition flex items-center gap-3">
            <i class="fas fa-cog text-emerald-600"></i><span class="text-sm font-semibold">الإعدادات</span>
        </a>
    `;
}

function renderStockAlerts(alerts) {
    if (!alerts) return '<p class="text-sm text-gray-500">لا توجد بيانات</p>';

    const total = alerts.low_stock_count + alerts.out_of_stock_count;
    if (total === 0) {
        return '<p class="text-sm text-green-700 bg-green-50 rounded-lg p-3"><i class="fas fa-check-circle ml-1"></i>المخزون بحالة جيدة</p>';
    }

    let html = `<p class="text-sm text-amber-800 bg-amber-50 rounded-lg p-3 mb-3">
        <strong>${total}</strong> تنبيه —
        <span class="text-red-700">${alerts.out_of_stock_count} نفد</span>،
        <span>${alerts.low_stock_count} منخفض (≤${alerts.low_stock_threshold})</span>
    </p><ul class="space-y-2 text-sm max-h-48 overflow-y-auto">`;

    [...(alerts.out_of_stock || []), ...(alerts.low_stock || [])].slice(0, 8).forEach(item => {
        const isOut = item.stock_quantity <= 0;
        html += `<li class="flex justify-between gap-2 border-b pb-2">
            <span class="truncate">${item.name}</span>
            <span class="shrink-0 font-semibold ${isOut ? 'text-red-600' : 'text-amber-600'}">${isOut ? 'نفد' : item.stock_quantity}</span>
        </li>`;
    });

    return html + '</ul>';
}

function renderExpiryAlerts(alerts) {
    if (!alerts) return '<p class="text-sm text-gray-500">لا توجد بيانات</p>';

    const total = (alerts.expired_count || 0) + (alerts.expiring_soon_count || 0);
    if (total === 0) {
        return '<p class="text-sm text-green-700 bg-green-50 rounded-lg p-3"><i class="fas fa-check-circle ml-1"></i>لا توجد أدوية منتهية أو قريبة الانتهاء</p>';
    }

    let html = `<p class="text-sm text-red-800 bg-red-50 rounded-lg p-3 mb-3">
        <strong>${total}</strong> تنبيه —
        <span class="text-red-700">${alerts.expired_count} منتهية</span>،
        <span class="text-amber-700">${alerts.expiring_soon_count} خلال ${alerts.warning_days} يوم</span>
    </p><ul class="space-y-2 text-sm max-h-48 overflow-y-auto">`;

    [...(alerts.expired || []), ...(alerts.expiring_soon || [])].slice(0, 8).forEach(item => {
        const isExpired = item.days_left < 0;
        html += `<li class="flex justify-between gap-2 border-b pb-2">
            <span class="truncate">${item.name}</span>
            <span class="shrink-0 font-semibold ${isExpired ? 'text-red-600' : 'text-amber-600'}">${isExpired ? 'منتهي' : item.days_left + ' يوم'}</span>
        </li>`;
    });

    return html + '</ul>';
}
</script>
@endsection
