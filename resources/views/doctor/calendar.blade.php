@extends('doctor.layout')

@section('title', 'التقويم')
@section('page-title', 'التقويم')
@section('page-description', 'إدارة المواعيد والجدول الزمني')

@section('content')
<!-- Calendar Header -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800" id="currentMonth">يونيو 2026</h2>
            <p class="text-gray-600 mt-1">إدارة مواعيدك</p>
        </div>
        <div class="flex gap-2">
            <button onclick="previousMonth()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="fas fa-chevron-right"></i>
            </button>
            <button onclick="goToToday()" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">
                اليوم
            </button>
            <button onclick="nextMonth()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
    </div>
</div>

<!-- Calendar Grid -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Calendar -->
    <div class="lg:col-span-3 bg-white rounded-xl shadow-sm p-6">
        <div class="grid grid-cols-7 gap-2 mb-4">
            <div class="text-center text-sm font-semibold text-gray-600 p-2">الأحد</div>
            <div class="text-center text-sm font-semibold text-gray-600 p-2">الإثنين</div>
            <div class="text-center text-sm font-semibold text-gray-600 p-2">الثلاثاء</div>
            <div class="text-center text-sm font-semibold text-gray-600 p-2">الأربعاء</div>
            <div class="text-center text-sm font-semibold text-gray-600 p-2">الخميس</div>
            <div class="text-center text-sm font-semibold text-gray-600 p-2">الجمعة</div>
            <div class="text-center text-sm font-semibold text-gray-600 p-2">السبت</div>
        </div>
        <div class="grid grid-cols-7 gap-2" id="calendarGrid">
            <!-- Calendar days will be added here -->
        </div>
    </div>

    <!-- Sidebar -->
    <div>
        <!-- Today's Schedule -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">جدول اليوم</h3>
            <div id="todaySchedule" class="space-y-3">
                <p class="text-gray-500">جاري التحميل...</p>
            </div>
        </div>

        <!-- Upcoming Appointments -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">المواعيد القادمة</h3>
            <div id="upcomingAppointments" class="space-y-3">
                <p class="text-gray-500">جاري التحميل...</p>
            </div>
        </div>
    </div>
</div>

<!-- Appointment Details Modal -->
<div id="appointmentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">تفاصيل الموعد</h3>
            <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="modalContent">
            <!-- Modal content will be added here -->
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let currentDate = new Date();
let selectedDate = null;

window.addEventListener('load', async function() {
    await loadCalendar();
    await loadTodaySchedule();
    await loadUpcomingAppointments();
});

async function loadCalendar() {
    try {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth() + 1;
        
        document.getElementById('currentMonth').textContent = getMonthName(month) + ' ' + year;
        
        const data = await apiCall(`/doctor/api/calendar?year=${year}&month=${month}`);
        
        if (data.success) {
            renderCalendar(data.data);
        }
    } catch (error) {
        console.error('Error loading calendar:', error);
    }
}

function renderCalendar(appointmentsByDate) {
    const grid = document.getElementById('calendarGrid');
    const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
    const startDay = firstDay.getDay();
    const totalDays = lastDay.getDate();
    
    let html = '';
    
    // Empty cells before first day
    for (let i = 0; i < startDay; i++) {
        html += '<div class="p-2"></div>';
    }
    
    // Days of month
    const today = new Date();
    for (let day = 1; day <= totalDays; day++) {
        const dateStr = `${currentDate.getFullYear()}-${String(currentDate.getMonth() + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const isToday = day === today.getDate() && currentDate.getMonth() === today.getMonth() && currentDate.getFullYear() === today.getFullYear();
        const appointments = appointmentsByDate[dateStr] || [];
        
        html += `
            <div onclick="selectDate('${dateStr}')" 
                 class="p-2 rounded-lg cursor-pointer hover:bg-gray-100 transition ${isToday ? 'bg-teal-100 border-2 border-teal-600' : ''}">
                <div class="text-sm font-semibold ${isToday ? 'text-teal-600' : 'text-gray-700'}">${day}</div>
                ${appointments.length > 0 ? `
                    <div class="mt-1 space-y-1">
                        ${appointments.slice(0, 2).map(app => `
                            <div class="text-xs px-1 py-0.5 rounded ${getStatusBgClass(app.status)} text-white truncate">
                                ${app.time}
                            </div>
                        `).join('')}
                        ${appointments.length > 2 ? `<div class="text-xs text-gray-500">+${appointments.length - 2} أكثر</div>` : ''}
                    </div>
                ` : ''}
            </div>
        `;
    }
    
    grid.innerHTML = html;
}

async function loadTodaySchedule() {
    try {
        const today = new Date().toISOString().split('T')[0];
        const data = await apiCall(`/doctor/api/appointments?date=${today}`);
        
        if (data.success) {
            renderTodaySchedule(data.data);
        }
    } catch (error) {
        console.error('Error loading today schedule:', error);
    }
}

function renderTodaySchedule(appointments) {
    const container = document.getElementById('todaySchedule');
    
    if (appointments.length === 0) {
        container.innerHTML = '<p class="text-gray-500">لا توجد مواعيد اليوم</p>';
        return;
    }
    
    container.innerHTML = appointments.map(appointment => `
        <div onclick="showAppointmentDetails('${appointment.id}')" class="p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-semibold text-gray-800">${appointment.patient_name || 'غير محدد'}</p>
                    <p class="text-sm text-gray-600">${appointment.time || '-'}</p>
                </div>
                <span class="px-2 py-1 rounded-full text-xs font-semibold ${getStatusClass(appointment.status)}">
                    ${getStatusText(appointment.status)}
                </span>
            </div>
        </div>
    `).join('');
}

async function loadUpcomingAppointments() {
    try {
        const data = await apiCall(`/doctor/api/appointments?status=confirmed&limit=5`);
        
        if (data.success) {
            renderUpcomingAppointments(data.data);
        }
    } catch (error) {
        console.error('Error loading upcoming appointments:', error);
    }
}

function renderUpcomingAppointments(appointments) {
    const container = document.getElementById('upcomingAppointments');
    
    if (appointments.length === 0) {
        container.innerHTML = '<p class="text-gray-500">لا توجد مواعيد قادمة</p>';
        return;
    }
    
    container.innerHTML = appointments.map(appointment => `
        <div onclick="showAppointmentDetails('${appointment.id}')" class="p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-semibold text-gray-800">${appointment.patient_name || 'غير محدد'}</p>
                    <p class="text-sm text-gray-600">${formatDate(appointment.date)} - ${appointment.time || '-'}</p>
                </div>
            </div>
        </div>
    `).join('');
}

function previousMonth() {
    currentDate.setMonth(currentDate.getMonth() - 1);
    loadCalendar();
}

function nextMonth() {
    currentDate.setMonth(currentDate.getMonth() + 1);
    loadCalendar();
}

function goToToday() {
    currentDate = new Date();
    loadCalendar();
}

async function selectDate(dateStr) {
    selectedDate = dateStr;
    try {
        const data = await apiCall(`/doctor/api/appointments?date=${dateStr}`);
        
        if (data.success) {
            showDateAppointments(data.data, dateStr);
        }
    } catch (error) {
        console.error('Error loading date appointments:', error);
    }
}

function showDateAppointments(appointments, dateStr) {
    const modal = document.getElementById('appointmentModal');
    const content = document.getElementById('modalContent');
    
    if (appointments.length === 0) {
        content.innerHTML = `
            <p class="text-gray-500 text-center py-4">لا توجد مواعد في ${formatDate(dateStr)}</p>
        `;
    } else {
        content.innerHTML = `
            <div class="space-y-3 max-h-96 overflow-y-auto">
                ${appointments.map(appointment => `
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-semibold text-gray-800">${appointment.patient_name || 'غير محدد'}</p>
                                <p class="text-sm text-gray-600">${appointment.time || '-'}</p>
                            </div>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold ${getStatusClass(appointment.status)}">
                                ${getStatusText(appointment.status)}
                            </span>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

async function showAppointmentDetails(appointmentId) {
    try {
        const data = await apiCall(`/doctor/api/appointments/${appointmentId}`);
        
        if (data.success) {
            const appointment = data.data;
            const modal = document.getElementById('appointmentModal');
            const content = document.getElementById('modalContent');
            
            content.innerHTML = `
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-600">المريض</p>
                        <p class="font-semibold text-gray-800">${appointment.patient_name || 'غير محدد'}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">التاريخ والوقت</p>
                        <p class="font-semibold text-gray-800">${formatDate(appointment.date)} - ${appointment.time || '-'}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">الحالة</p>
                        <span class="px-2 py-1 rounded-full text-xs font-semibold ${getStatusClass(appointment.status)}">
                            ${getStatusText(appointment.status)}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">ملاحظات</p>
                        <p class="text-gray-700">${appointment.notes || 'لا توجد ملاحظات'}</p>
                    </div>
                </div>
            `;
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    } catch (error) {
        console.error('Error loading appointment details:', error);
    }
}

function closeModal() {
    const modal = document.getElementById('appointmentModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function getMonthName(month) {
    const months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
    return months[month - 1];
}

function getStatusClass(status) {
    const classes = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'confirmed': 'bg-blue-100 text-blue-800',
        'completed': 'bg-green-100 text-green-800',
        'cancelled': 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

function getStatusBgClass(status) {
    const classes = {
        'pending': 'bg-yellow-500',
        'confirmed': 'bg-blue-500',
        'completed': 'bg-green-500',
        'cancelled': 'bg-red-500',
    };
    return classes[status] || 'bg-gray-500';
}

function getStatusText(status) {
    const texts = {
        'pending': 'معلق',
        'confirmed': 'مؤكد',
        'completed': 'مكتمل',
        'cancelled': 'ملغي',
    };
    return texts[status] || status;
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-IQ');
}
</script>
@endsection
