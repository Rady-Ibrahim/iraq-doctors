@extends('admin.layout')

@section('title', 'لوحة تحكم الإدارة - الرئيسية')
@section('page-title', 'نظرة عامة')
@section('page-description', 'إحصائيات النظام والمقاييس الرئيسية')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Doctors -->
    <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">إجمالي الأطباء</p>
                <h3 class="text-3xl font-bold text-gray-800 mt-2" id="totalDoctors">-</h3>
                <p class="text-sm text-green-600 mt-2" id="activeDoctorsPercent">-</p>
            </div>
            <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user-md text-blue-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Total Patients -->
    <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">إجمالي المرضى</p>
                <h3 class="text-3xl font-bold text-gray-800 mt-2" id="totalPatients">-</h3>
                <p class="text-sm text-gray-500 mt-2" id="ghostPatientsPercent">-</p>
            </div>
            <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-green-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Total Appointments -->
    <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-purple-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">إجمالي المواعيد</p>
                <h3 class="text-3xl font-bold text-gray-800 mt-2" id="totalAppointments">-</h3>
                <p class="text-sm text-purple-600 mt-2" id="completedAppointmentsPercent">-</p>
            </div>
            <div class="w-14 h-14 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-calendar-check text-purple-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Total Revenue -->
    <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-yellow-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">إجمالي الإيرادات</p>
                <h3 class="text-3xl font-bold text-gray-800 mt-2" id="totalRevenue">-</h3>
                <p class="text-sm text-yellow-600 mt-2" id="revenueGrowth">-</p>
            </div>
            <div class="w-14 h-14 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fas fa-dollar-sign text-yellow-600 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Pending Doctors Section -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-8">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-800">الأطباء المعلقين</h3>
        <a href="/admin/dashboard/doctors?status=pending" class="text-blue-600 hover:text-blue-700 text-sm">عرض الكل</a>
    </div>
    <div id="pendingDoctors" class="space-y-4">
        <!-- Pending doctors will be loaded here -->
        <div class="text-center text-gray-500 py-8">
            <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
            <p>جاري التحميل...</p>
        </div>
    </div>
</div>

<!-- Recent Activity & Analytics -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Appointments -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800">المواعيد الأخيرة</h3>
            <a href="/admin/dashboard/appointments" class="text-blue-600 hover:text-blue-700 text-sm">عرض الكل</a>
        </div>
        <div id="recentAppointments" class="space-y-4">
            <!-- Appointments will be loaded here -->
            <div class="text-center text-gray-500 py-8">
                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                <p>جاري التحميل...</p>
            </div>
        </div>
    </div>

    <!-- Revenue Chart Placeholder -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800">الإيرادات الشهرية</h3>
            <a href="/admin/dashboard/analytics" class="text-blue-600 hover:text-blue-700 text-sm">عرض التفاصيل</a>
        </div>
        <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
            <div class="text-center text-gray-500">
                <i class="fas fa-chart-line text-4xl mb-2"></i>
                <p>الرسم البياني للإيرادات</p>
                <p class="text-sm">انتقل إلى صفحة التحليلات</p>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    window.addEventListener('load', async function() {
        await loadDashboardMetrics();
        await loadPendingDoctors();
        await loadRecentAppointments();
    });

    async function loadDashboardMetrics() {
        try {
            const data = await apiCall('/admin/api/metrics');
            
            if (data.success) {
                const metrics = data.data;

                // Update stats
                document.getElementById('totalDoctors').textContent = metrics.doctors.total || 0;
                document.getElementById('activeDoctorsPercent').textContent = `${metrics.doctors.active || 0} نشط`;

                document.getElementById('totalPatients').textContent = metrics.patients.total || 0;
                document.getElementById('ghostPatientsPercent').textContent = `${metrics.patients.ghost || 0} ghost`;

                document.getElementById('totalAppointments').textContent = metrics.appointments.total || 0;
                document.getElementById('completedAppointmentsPercent').textContent = `${metrics.appointments.completed || 0} مكتمل`;

                document.getElementById('totalRevenue').textContent = formatCurrency(metrics.revenue.total || 0);
                document.getElementById('revenueGrowth').textContent = `${metrics.revenue.growth || 0}% نمو`;
            }
        } catch (error) {
            console.error('Error loading metrics:', error);
        }
    }

    async function loadPendingDoctors() {
        try {
            const data = await apiCall('/admin/api/doctors?status=pending&limit=5');
            
            if (data.success) {
                const container = document.getElementById('pendingDoctors');
                
                if (data.data.length === 0) {
                    container.innerHTML = `
                        <div class="text-center text-gray-500 py-8">
                            <i class="fas fa-check-circle text-green-500 text-2xl mb-2"></i>
                            <p>لا يوجد أطباء معلقين</p>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = data.data.map(doctor => `
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user-md text-blue-600"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800">${doctor.name || 'غير محدد'}</h4>
                                <p class="text-sm text-gray-500">${doctor.speciality || 'غير محدد'}</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="approveDoctor('${doctor.id}')" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                                <i class="fas fa-check"></i>
                            </button>
                            <button onclick="rejectDoctor('${doctor.id}')" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `).join('');
            }
        } catch (error) {
            console.error('Error loading pending doctors:', error);
        }
    }

    async function loadRecentAppointments() {
        try {
            const data = await apiCall('/admin/api/appointments?limit=5');
            
            if (data.success) {
                const container = document.getElementById('recentAppointments');
                
                if (data.data.length === 0) {
                    container.innerHTML = `
                        <div class="text-center text-gray-500 py-8">
                            <p>لا توجد مواعيد</p>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = data.data.map(appointment => `
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-calendar text-purple-600"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800">${appointment.patient_name || 'مريض'}</h4>
                                <p class="text-sm text-gray-500">${appointment.doctor_name || 'طبيب'}</p>
                            </div>
                        </div>
                        <div class="text-left">
                            <p class="font-semibold text-gray-800">${appointment.date || '-'}</p>
                            <span class="text-xs px-2 py-1 rounded-full ${getStatusClass(appointment.status)}">
                                ${getStatusText(appointment.status)}
                            </span>
                        </div>
                    </div>
                `).join('');
            }
        } catch (error) {
            console.error('Error loading appointments:', error);
        }
    }

    async function approveDoctor(doctorId) {
        if (!confirm('هل أنت متأكد من الموافقة على هذا الطبيب؟')) return;

        try {
            const data = await apiCall(`/admin/api/doctors/${doctorId}/approve`, {
                method: 'POST'
            });

            if (data.success) {
                alert('تمت الموافقة بنجاح');
                loadPendingDoctors();
            } else {
                alert(data.error?.message || 'فشلت العملية');
            }
        } catch (error) {
            alert('حدث خطأ أثناء الموافقة');
        }
    }

    async function rejectDoctor(doctorId) {
        if (!confirm('هل أنت متأكد من رفض هذا الطبيب؟')) return;

        try {
            const data = await apiCall(`/admin/api/doctors/${doctorId}/reject`, {
                method: 'POST'
            });

            if (data.success) {
                alert('تم الرفض بنجاح');
                loadPendingDoctors();
            } else {
                alert(data.error?.message || 'فشلت العملية');
            }
        } catch (error) {
            alert('حدث خطأ أثناء الرفض');
        }
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('ar-IQ', {
            style: 'currency',
            currency: 'IQD',
            minimumFractionDigits: 0
        }).format(amount);
    }

    function getStatusClass(status) {
        const classes = {
            'pending': 'bg-yellow-100 text-yellow-800',
            'confirmed': 'bg-blue-100 text-blue-800',
            'completed': 'bg-green-100 text-green-800',
            'cancelled': 'bg-red-100 text-red-800',
            'no_show': 'bg-gray-100 text-gray-800'
        };
        return classes[status] || 'bg-gray-100 text-gray-800';
    }

    function getStatusText(status) {
        const texts = {
            'pending': 'معلق',
            'confirmed': 'مؤكد',
            'completed': 'مكتمل',
            'cancelled': 'ملغي',
            'no_show': 'لم يحضر'
        };
        return texts[status] || status;
    }
</script>
@endsection
