@extends('admin.layout')

@section('title', 'التحليلات')
@section('page-title', 'التحليلات')
@section('page-description', 'إحصائيات متقدمة وتحليلات النظام')

@section('content')
<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">الفترة</label>
            <select id="periodFilter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="today">اليوم</option>
                <option value="week" selected>هذا الأسبوع</option>
                <option value="month">هذا الشهر</option>
                <option value="year">هذه السنة</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">النوع</label>
            <select id="typeFilter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">جميع الأنواع</option>
                <option value="doctors">الأطباء</option>
                <option value="patients">المرضى</option>
                <option value="appointments">المواعيد</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">&nbsp;</label>
            <button onclick="applyFilters()" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-filter ml-2"></i>تصفية
            </button>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">إجمالي المستخدمين</p>
                <p class="text-2xl font-bold text-gray-800" id="totalUsers">0</p>
                <p class="text-xs mt-1" id="usersGrowth">—</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-blue-600"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">الأطباء النشطين</p>
                <p class="text-2xl font-bold text-gray-800" id="activeDoctors">0</p>
                <p class="text-xs mt-1" id="doctorsGrowth">—</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user-md text-green-600"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">المواعيد اليومية</p>
                <p class="text-2xl font-bold text-gray-800" id="dailyAppointments">0</p>
                <p class="text-xs mt-1" id="appointmentsGrowth">—</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-calendar-check text-purple-600"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">معدل التحويل</p>
                <p class="text-2xl font-bold text-gray-800" id="conversionRate">0%</p>
                <p class="text-xs text-gray-500 mt-1">مواعيد / مستخدمين</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                <i class="fas fa-percentage text-orange-600"></i>
            </div>
        </div>
    </div>
</div>

<!-- Charts Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- User Growth Chart -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">نمو المستخدمين</h3>
        <div class="h-64 relative">
            <canvas id="userGrowthCanvas"></canvas>
        </div>
    </div>

    <!-- Appointments Chart -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">المواعيد حسب الحالة</h3>
        <div class="h-64 relative">
            <canvas id="appointmentsStatusCanvas"></canvas>
        </div>
    </div>
</div>

<!-- Detailed Analytics -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Top Specialities -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">أكثر التخصصات طلباً</h3>
        <div class="space-y-4" id="topSpecialities">
            <p class="text-gray-500">جاري التحميل...</p>
        </div>
    </div>

    <!-- User Demographics -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">التوزيع الديموغرافي</h3>
        <div class="space-y-4" id="demographics">
            <p class="text-gray-500">جاري التحميل...</p>
        </div>
    </div>

    <!-- Peak Hours -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">ساعات الذروة</h3>
        <div class="space-y-4" id="peakHours">
            <p class="text-gray-500">جاري التحميل...</p>
        </div>
    </div>
</div>

<!-- Geographic Distribution -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">التوزيع الجغرافي</h3>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4" id="geographicDistribution">
        <p class="text-gray-500">جاري التحميل...</p>
    </div>
</div>

@endsection

@section('scripts')
<script>
let userGrowthChart = null;
let appointmentsStatusChart = null;

window.addEventListener('load', async function() {
    await loadAnalyticsData();
});

async function loadAnalyticsData() {
    try {
        showLoading();
        
        const period = document.getElementById('periodFilter').value;
        const type = document.getElementById('typeFilter').value;
        
        const params = new URLSearchParams({
            period,
            type,
        });

        const data = await apiCall(`/admin/api/analytics?${params}`);
        
        if (data.success) {
            renderAnalyticsData(data.data);
        } else {
            alert(data.error?.message || 'فشل تحميل البيانات');
        }
    } catch (error) {
        console.error('Error loading analytics data:', error);
        alert('حدث خطأ أثناء تحميل البيانات');
    } finally {
        hideLoading();
    }
}

function formatGrowth(elId, value) {
    const el = document.getElementById(elId);
    if (!el) return;
    const n = Number(value || 0);
    el.textContent = `${n >= 0 ? '+' : ''}${n}% عن الفترة السابقة`;
    el.className = `text-xs mt-1 ${n >= 0 ? 'text-green-600' : 'text-red-600'}`;
}

function renderAnalyticsData(data) {
    document.getElementById('totalUsers').textContent = data.total_users || 0;
    document.getElementById('activeDoctors').textContent = data.active_doctors || 0;
    document.getElementById('dailyAppointments').textContent = data.daily_appointments || 0;
    document.getElementById('conversionRate').textContent = (data.conversion_rate || 0) + '%';

    formatGrowth('usersGrowth', data.growth?.users);
    formatGrowth('doctorsGrowth', data.growth?.doctors);
    formatGrowth('appointmentsGrowth', data.growth?.appointments);

    renderUserGrowthChart(data.charts?.user_growth);
    renderAppointmentsStatusChart(data.charts?.appointments_by_status);

    renderTopSpecialities(data.top_specialities || []);
    renderDemographics(data.demographics || {});
    renderPeakHours(data.peak_hours || []);
    renderGeographicDistribution(data.geographic_distribution || []);
}

function renderUserGrowthChart(series) {
    const canvas = document.getElementById('userGrowthCanvas');
    if (!canvas || typeof Chart === 'undefined') return;

    if (userGrowthChart) userGrowthChart.destroy();

    userGrowthChart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: series?.labels || [],
            datasets: [{
                label: 'مستخدمون جدد',
                data: series?.values || [],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.15)',
                fill: true,
                tension: 0.35,
                pointRadius: 2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
                x: {
                    ticks: {
                        maxTicksLimit: 8,
                        maxRotation: 0,
                    },
                },
            },
        },
    });
}

function renderAppointmentsStatusChart(series) {
    const canvas = document.getElementById('appointmentsStatusCanvas');
    if (!canvas || typeof Chart === 'undefined') return;

    if (appointmentsStatusChart) appointmentsStatusChart.destroy();

    const colors = ['#f59e0b', '#3b82f6', '#22c55e', '#ef4444', '#6b7280'];

    appointmentsStatusChart = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: series?.labels || [],
            datasets: [{
                data: series?.values || [],
                backgroundColor: colors,
                borderWidth: 0,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { family: 'Cairo' } },
                },
            },
        },
    });
}

function renderTopSpecialities(specialities) {
    const container = document.getElementById('topSpecialities');
    
    if (specialities.length === 0) {
        container.innerHTML = '<p class="text-gray-500">لا توجد بيانات</p>';
        return;
    }

    container.innerHTML = specialities.map((spec, index) => `
        <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <span class="font-semibold text-blue-600">${index + 1}</span>
            </div>
            <div class="flex-1">
                <p class="font-semibold text-gray-800">${spec.name || 'غير محدد'}</p>
                <p class="text-sm text-gray-600">${spec.count || 0} طبيب</p>
            </div>
            <div class="text-left">
                <p class="font-semibold text-gray-800">${spec.percentage || 0}%</p>
            </div>
        </div>
    `).join('');
}

function renderDemographics(demographics) {
    const container = document.getElementById('demographics');
    
    if (!demographics || Object.keys(demographics).length === 0) {
        container.innerHTML = '<p class="text-gray-500">لا توجد بيانات</p>';
        return;
    }

    container.innerHTML = Object.entries(demographics).map(([key, value]) => `
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-semibold text-gray-700">${getDemographicLabel(key)}</span>
                <span class="text-sm font-semibold text-gray-800">${value || 0}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full" style="width: ${(value / 100) * 100}%"></div>
            </div>
        </div>
    `).join('');
}

function renderPeakHours(hours) {
    const container = document.getElementById('peakHours');
    
    if (hours.length === 0) {
        container.innerHTML = '<p class="text-gray-500">لا توجد بيانات</p>';
        return;
    }

    container.innerHTML = hours.map((hour, index) => `
        <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
            <div class="flex-1">
                <p class="font-semibold text-gray-800">${hour.time_range}</p>
                <p class="text-sm text-gray-600">${hour.count || 0} موعد</p>
            </div>
            <div class="text-left">
                <p class="font-semibold text-gray-800">${hour.percentage || 0}%</p>
            </div>
        </div>
    `).join('');
}

function renderGeographicDistribution(locations) {
    const container = document.getElementById('geographicDistribution');
    
    if (locations.length === 0) {
        container.innerHTML = '<p class="text-gray-500">لا توجد بيانات</p>';
        return;
    }

    container.innerHTML = locations.map(location => `
        <div class="p-4 bg-gray-50 rounded-lg">
            <p class="font-semibold text-gray-800">${location.name || 'غير محدد'}</p>
            <p class="text-2xl font-bold text-blue-600 mt-2">${location.count || 0}</p>
            <p class="text-sm text-gray-600">${location.percentage || 0}%</p>
        </div>
    `).join('');
}

function getDemographicLabel(key) {
    const labels = {
        'male': 'ذكور',
        'female': 'إناث',
        'age_18_25': '18-25',
        'age_26_35': '26-35',
        'age_36_45': '36-45',
        'age_46_plus': '46+',
    };
    return labels[key] || key;
}

function applyFilters() {
    loadAnalyticsData();
}
</script>
@endsection
