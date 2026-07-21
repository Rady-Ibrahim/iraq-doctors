@extends('admin.layout')

@section('title', 'تقارير الطلبات')
@section('page-title', 'التقارير')
@section('page-description', 'إحصائيات طلبات المختبرات والصيدليات')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input type="date" id="dateFrom" class="w-full px-4 py-2 border rounded-lg">
        <input type="date" id="dateTo" class="w-full px-4 py-2 border rounded-lg">
        <button onclick="loadReport()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">تحديث</button>
        <button onclick="downloadPdf()" class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700">
            <i class="fas fa-file-pdf ml-2"></i>تحميل PDF
        </button>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-cyan-500">
        <p class="text-gray-500 text-sm">طلبات المختبرات</p>
        <h3 class="text-3xl font-bold mt-2" id="labTotal">-</h3>
        <p class="text-sm text-cyan-600 mt-2">مكتملة: <span id="labDelivered">-</span></p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-emerald-500">
        <p class="text-gray-500 text-sm">طلبات الصيدليات</p>
        <h3 class="text-3xl font-bold mt-2" id="pharmacyTotal">-</h3>
        <p class="text-sm text-emerald-600 mt-2">مكتملة: <span id="pharmacyCompleted">-</span></p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-amber-500">
        <p class="text-gray-500 text-sm">إجمالي قيمة الطلبات المكتملة</p>
        <h3 class="text-3xl font-bold mt-2" id="combinedRevenue">-</h3>
        <p class="text-sm text-gray-500 mt-2">مختبر + صيدلية</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-bold text-gray-800 mb-4">توزيع طلبات المختبرات</h3>
        <div id="labStatusBreakdown" class="space-y-2 text-sm"></div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-bold text-gray-800 mb-4">توزيع طلبات الصيدليات</h3>
        <div id="pharmacyStatusBreakdown" class="space-y-2 text-sm"></div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function renderBreakdown(containerId, byStatus) {
    const el = document.getElementById(containerId);
    const entries = Object.entries(byStatus || {}).filter(([, v]) => v.count > 0);
    if (!entries.length) {
        el.innerHTML = '<p class="text-gray-500">لا توجد بيانات</p>';
        return;
    }
    el.innerHTML = entries.map(([, v]) => `
        <div class="flex justify-between items-center py-2 border-b">
            <span>${v.label}</span>
            <span class="font-semibold">${v.count}</span>
        </div>
    `).join('');
}

async function loadReport() {
    const params = new URLSearchParams();
    const from = document.getElementById('dateFrom').value;
    const to = document.getElementById('dateTo').value;
    if (from) params.set('date_from', from);
    if (to) params.set('date_to', to);

    const data = await apiCall(`/admin/api/reports/orders?${params}`);
    if (!data?.success) return;

    const r = data.data;
    document.getElementById('labTotal').textContent = r.laboratory?.total ?? 0;
    document.getElementById('labDelivered').textContent = r.laboratory?.delivered ?? 0;
    document.getElementById('pharmacyTotal').textContent = r.pharmacy?.total ?? 0;
    document.getElementById('pharmacyCompleted').textContent = r.pharmacy?.completed ?? 0;
    document.getElementById('combinedRevenue').textContent = formatCurrency(r.combined_revenue ?? 0);

    renderBreakdown('labStatusBreakdown', r.laboratory?.by_status);
    renderBreakdown('pharmacyStatusBreakdown', r.pharmacy?.by_status);
}

function downloadPdf() {
    const params = new URLSearchParams();
    const from = document.getElementById('dateFrom').value;
    const to = document.getElementById('dateTo').value;
    if (from) params.set('date_from', from);
    if (to) params.set('date_to', to);
    window.location.href = `{{ route('admin.reports.orders.pdf') }}?${params}`;
}

document.addEventListener('DOMContentLoaded', loadReport);
</script>
@endsection
