@extends('admin.layout')

@section('title', 'الاشتراكات')
@section('page-title', 'اشتراكات الأطباء')
@section('page-description', 'إدارة خطط الاشتراك واشتراكات الأطباء')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <p class="text-sm text-gray-600">اشتراكات نشطة</p>
        <p class="text-2xl font-bold text-green-600" id="activeCount">0</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <p class="text-sm text-gray-600">منتهية</p>
        <p class="text-2xl font-bold text-gray-800" id="expiredCount">0</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <p class="text-sm text-gray-600">بانتظار الدفع</p>
        <p class="text-2xl font-bold text-yellow-600" id="pendingCount">0</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <p class="text-sm text-gray-600">إجمالي الإيرادات</p>
        <p class="text-2xl font-bold text-blue-600" id="totalRevenue">0</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">خطط الاشتراك</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4" id="plansGrid">
        <p class="text-gray-500">جاري التحميل...</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-800">اشتراكات الأطباء</h3>
        <select id="statusFilter" onchange="loadSubscriptions()" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">جميع الحالات</option>
            <option value="active">نشط</option>
            <option value="expired">منتهي</option>
            <option value="pending_payment">بانتظار الدفع</option>
            <option value="cancelled">ملغي</option>
        </select>
    </div>
    <table class="w-full">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الطبيب</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الخطة</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">المبلغ</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الفترة</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الحالة</th>
            </tr>
        </thead>
        <tbody id="subscriptionsTableBody">
            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">جاري التحميل...</td></tr>
        </tbody>
    </table>
</div>
@endsection

@section('scripts')
<script>
window.addEventListener('load', loadSubscriptions);

async function loadSubscriptions() {
    try {
        showLoading();
        const status = document.getElementById('statusFilter').value;
        const params = new URLSearchParams({ limit: 50 });
        if (status) params.set('status', status);

        const data = await apiCall(`/admin/api/subscriptions?${params}`);
        if (!data?.success) {
            document.getElementById('subscriptionsTableBody').innerHTML =
                '<tr><td colspan="5" class="px-6 py-8 text-center text-red-600">تعذر تحميل البيانات</td></tr>';
            return;
        }

        const { summary, plans, subscriptions } = data.data;
        document.getElementById('activeCount').textContent = summary.active || 0;
        document.getElementById('expiredCount').textContent = summary.expired || 0;
        document.getElementById('pendingCount').textContent = summary.pending_payment || 0;
        document.getElementById('totalRevenue').textContent = formatCurrency(summary.total_revenue || 0);

        renderPlans(plans || []);
        renderSubscriptions(subscriptions || []);
    } catch (e) {
        console.error(e);
    } finally {
        hideLoading();
    }
}

function renderPlans(plans) {
    const grid = document.getElementById('plansGrid');
    if (!plans.length) {
        grid.innerHTML = '<p class="text-gray-500">لا توجد خطط — شغّل php artisan db:seed --class=SubscriptionPlanSeeder</p>';
        return;
    }
    grid.innerHTML = plans.map(plan => `
        <div class="p-4 bg-gray-50 rounded-lg border">
            <p class="font-bold text-gray-800">${plan.name}</p>
            <p class="text-blue-600 font-semibold mt-1">${formatCurrency(plan.price)} / ${plan.duration_days} يوم</p>
            <p class="text-sm text-gray-600 mt-2">${plan.subscribers_count || 0} مشترك</p>
        </div>
    `).join('');
}

function renderSubscriptions(items) {
    const tbody = document.getElementById('subscriptionsTableBody');
    if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">لا توجد اشتراكات</td></tr>';
        return;
    }
    tbody.innerHTML = items.map(sub => `
        <tr class="border-b hover:bg-gray-50">
            <td class="px-6 py-4 font-semibold">${sub.doctor_name || '-'}</td>
            <td class="px-6 py-4">${sub.plan_name || '-'}</td>
            <td class="px-6 py-4">${formatCurrency(sub.amount_paid || 0)}</td>
            <td class="px-6 py-4 text-sm text-gray-600">${sub.start_date || '-'} → ${sub.end_date || '-'}</td>
            <td class="px-6 py-4"><span class="px-2 py-1 rounded-full text-xs ${statusClass(sub.status)}">${statusText(sub.status)}</span></td>
        </tr>
    `).join('');
}

function statusClass(s) {
    return ({ active: 'bg-green-100 text-green-800', expired: 'bg-gray-100 text-gray-800', pending_payment: 'bg-yellow-100 text-yellow-800', cancelled: 'bg-red-100 text-red-800' })[s] || 'bg-gray-100';
}
function statusText(s) {
    return ({ active: 'نشط', expired: 'منتهي', pending_payment: 'بانتظار الدفع', cancelled: 'ملغي' })[s] || s;
}
function formatCurrency(amount) {
    return new Intl.NumberFormat('ar-IQ', { style: 'currency', currency: 'IQD', minimumFractionDigits: 0 }).format(amount);
}
</script>
@endsection
