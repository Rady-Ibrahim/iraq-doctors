@extends('doctor.layout')

@section('title', 'لوحة تحكم الطبيب - الرئيسية')
@section('page-title', 'نظرة عامة')
@section('page-description', 'إحصائياتك وأنشطتك اليومية')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Patients -->
    <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-teal-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">إجمالي المرضى</p>
                <h3 class="text-3xl font-bold text-gray-800 mt-2" id="totalPatients">-</h3>
                <p class="text-sm text-teal-600 mt-2" id="newPatientsCount">-</p>
            </div>
            <div class="w-14 h-14 bg-teal-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-teal-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Today's Appointments -->
    <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">مواعيد اليوم</p>
                <h3 class="text-3xl font-bold text-gray-800 mt-2" id="todayAppointments">-</h3>
                <p class="text-sm text-blue-600 mt-2" id="upcomingAppointments">-</p>
            </div>
            <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-calendar-check text-blue-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Prescriptions -->
    <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-purple-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">الوصفات الصادرة</p>
                <h3 class="text-3xl font-bold text-gray-800 mt-2" id="totalPrescriptions">-</h3>
                <p class="text-sm text-purple-600 mt-2" id="thisMonthPrescriptions">-</p>
            </div>
            <div class="w-14 h-14 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-prescription text-purple-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Reviews -->
    <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-yellow-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">التقييمات</p>
                <h3 class="text-3xl font-bold text-gray-800 mt-2" id="averageRating">-</h3>
                <p class="text-sm text-yellow-600 mt-2" id="totalReviews">-</p>
            </div>
            <div class="w-14 h-14 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fas fa-star text-yellow-600 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Today's Activity & Upcoming Tasks -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Today's Appointments -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800">مواعيد اليوم</h3>
            <a href="/doctor/dashboard/calendar" class="text-teal-600 hover:text-teal-700 text-sm">عرض التقويم</a>
        </div>
        <div id="todayActivity" class="space-y-4">
            <!-- Appointments will be loaded here -->
            <div class="text-center text-gray-500 py-8">
                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                <p>جاري التحميل...</p>
            </div>
        </div>
    </div>

    <!-- Upcoming Tasks -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800">المهام القادمة</h3>
            <a href="/doctor/dashboard/patients" class="text-teal-600 hover:text-teal-700 text-sm">عرض الكل</a>
        </div>
        <div id="upcomingTasks" class="space-y-4">
            <!-- Tasks will be loaded here -->
            <div class="text-center text-gray-500 py-8">
                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                <p>جاري التحميل...</p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Patients -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-800">المرضى الأخيرين</h3>
        <a href="/doctor/dashboard/patients" class="text-teal-600 hover:text-teal-700 text-sm">عرض الكل</a>
    </div>
    <div id="recentPatients" class="overflow-x-auto">
        <!-- Patients table will be loaded here -->
        <div class="text-center text-gray-500 py-8">
            <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
            <p>جاري التحميل...</p>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    window.addEventListener('load', async function() {
        await loadDashboardMetrics();
        await loadTodayActivity();
        await loadUpcomingTasks();
        await loadRecentPatients();
    });

    async function loadDashboardMetrics() {
        try {
            const data = await apiCall('/doctor/dashboard/metrics');
            
            if (data.success) {
                const metrics = data.data;

                // Update stats
                document.getElementById('totalPatients').textContent = metrics.patients.total || 0;
                document.getElementById('newPatientsCount').textContent = `${metrics.patients.new_this_month || 0} جديد هذا الشهر`;

                document.getElementById('todayAppointments').textContent = metrics.appointments.today || 0;
                document.getElementById('upcomingAppointments').textContent = `${metrics.appointments.upcoming || 0} قادم`;

                document.getElementById('totalPrescriptions').textContent = metrics.prescriptions.total || 0;
                document.getElementById('thisMonthPrescriptions').textContent = `${metrics.prescriptions.this_month || 0} هذا الشهر`;

                document.getElementById('averageRating').textContent = metrics.reviews.average_rating || '0.0';
                document.getElementById('totalReviews').textContent = `${metrics.reviews.total || 0} تقييم`;
            }
        } catch (error) {
            console.error('Error loading metrics:', error);
        }
    }

    async function loadTodayActivity() {
        try {
            const data = await apiCall('/doctor/dashboard/today-activity');
            
            if (data.success) {
                const container = document.getElementById('todayActivity');
                const appointments = data.data.appointments || [];
                
                if (appointments.length === 0) {
                    container.innerHTML = `
                        <div class="text-center text-gray-500 py-8">
                            <i class="fas fa-calendar-check text-gray-400 text-2xl mb-2"></i>
                            <p>لا توجد مواعيد لليوم</p>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = appointments.map(appointment => `
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-blue-600"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800">${appointment.patient_name || 'مريض'}</h4>
                                <p class="text-sm text-gray-500">${appointment.time || '-'}</p>
                            </div>
                        </div>
                        <div class="text-left">
                            <span class="text-xs px-3 py-1 rounded-full ${getAppointmentStatusClass(appointment.status)}">
                                ${getAppointmentStatusText(appointment.status)}
                            </span>
                        </div>
                    </div>
                `).join('');
            }
        } catch (error) {
            console.error('Error loading today activity:', error);
        }
    }

    async function loadUpcomingTasks() {
        try {
            const data = await apiCall('/doctor/dashboard/upcoming-tasks');
            
            if (data.success) {
                const container = document.getElementById('upcomingTasks');
                const tasks = data.data.tasks || [];
                
                if (tasks.length === 0) {
                    container.innerHTML = `
                        <div class="text-center text-gray-500 py-8">
                            <i class="fas fa-tasks text-gray-400 text-2xl mb-2"></i>
                            <p>لا توجد مهام قادمة</p>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = tasks.map(task => `
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-teal-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-${task.type === 'appointment' ? 'calendar' : 'clipboard'} text-teal-600"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800">${task.title || 'مهمة'}</h4>
                                <p class="text-sm text-gray-500">${task.date || '-'}</p>
                            </div>
                        </div>
                        <span class="text-xs text-gray-500">${task.time || ''}</span>
                    </div>
                `).join('');
            }
        } catch (error) {
            console.error('Error loading upcoming tasks:', error);
        }
    }

    async function loadRecentPatients() {
        try {
            const data = await apiCall('/doctor/dashboard/patients?limit=5');
            
            if (data.success) {
                const container = document.getElementById('recentPatients');
                const patients = data.data || [];
                
                if (patients.length === 0) {
                    container.innerHTML = `
                        <div class="text-center text-gray-500 py-8">
                            <i class="fas fa-users text-gray-400 text-2xl mb-2"></i>
                            <p>لا يوجد مرضى</p>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = `
                    <table class="w-full">
                        <thead>
                            <tr class="text-right text-gray-500 text-sm border-b">
                                <th class="pb-3">الاسم</th>
                                <th class="pb-3">رقم الهاتف</th>
                                <th class="pb-3">آخر زيارة</th>
                                <th class="pb-3">الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${patients.map(patient => `
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-teal-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-user text-teal-600 text-sm"></i>
                                            </div>
                                            <span class="font-medium">${patient.name || 'غير محدد'}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 text-gray-600">${patient.phone || '-'}</td>
                                    <td class="py-3 text-gray-600">${patient.last_visit || '-'}</td>
                                    <td class="py-3">
                                        <a href="/doctor/dashboard/patients/${patient.id}" class="text-teal-600 hover:text-teal-700">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }
        } catch (error) {
            console.error('Error loading recent patients:', error);
        }
    }

    function getAppointmentStatusClass(status) {
        const classes = {
            'pending': 'bg-yellow-100 text-yellow-800',
            'confirmed': 'bg-blue-100 text-blue-800',
            'completed': 'bg-green-100 text-green-800',
            'cancelled': 'bg-red-100 text-red-800',
            'no_show': 'bg-gray-100 text-gray-800'
        };
        return classes[status] || 'bg-gray-100 text-gray-800';
    }

    function getAppointmentStatusText(status) {
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
