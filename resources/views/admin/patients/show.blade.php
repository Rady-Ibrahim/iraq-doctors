@extends('admin.layout')

@section('title', 'تفاصيل المريض')
@section('page-title', 'تفاصيل المريض')
@section('page-description', 'عرض معلومات المريض الكاملة')

@section('content')
<!-- Back Button -->
<div class="mb-6">
    <a href="/admin/dashboard/patients" class="text-blue-600 hover:text-blue-700 flex items-center gap-2">
        <i class="fas fa-arrow-right"></i>
        <span>العودة إلى قائمة المرضى</span>
    </a>
</div>

<!-- Patient Info -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Info -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-start gap-6 mb-6">
                <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-green-600 text-4xl"></i>
                </div>
                <div class="flex-1">
                    <h2 class="text-2xl font-bold text-gray-800" id="patientName">جاري التحميل...</h2>
                    <p class="text-gray-600 mt-1" id="patientPhone">-</p>
                    <div class="flex gap-4 mt-4">
                        <div>
                            <p class="text-sm text-gray-600">المواعيد</p>
                            <p class="text-lg font-semibold text-gray-800" id="totalAppointments">0</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">النوع</p>
                            <p class="text-lg font-semibold text-gray-800" id="patientType">-</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">معلومات الاتصال</h3>
                <div class="space-y-2">
                    <p class="text-gray-700">
                        <span class="font-semibold">الهاتف:</span>
                        <span id="patientPhoneFull">-</span>
                    </p>
                    <p class="text-gray-700">
                        <span class="font-semibold">البريد الإلكتروني:</span>
                        <span id="patientEmail">-</span>
                    </p>
                    <p class="text-gray-700">
                        <span class="font-semibold">تاريخ التسجيل:</span>
                        <span id="patientJoinedDate">-</span>
                    </p>
                </div>
            </div>

            <!-- Status -->
            <div class="border-t mt-6 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">الحالة</h3>
                <div class="flex items-center gap-4">
                    <span class="px-4 py-2 rounded-full text-sm font-semibold" id="patientStatusBadge">
                        -
                    </span>
                    <div id="actionButtons" class="flex gap-2">
                        <!-- Action buttons will be added here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Appointments -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">المواعيد الأخيرة</h3>
            <div id="recentAppointments" class="space-y-3">
                <p class="text-gray-500">جاري التحميل...</p>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div>
        <!-- Statistics -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">الإحصائيات</h3>
            <div class="space-y-4">
                <div>
                    <p class="text-sm text-gray-600">إجمالي المواعيد</p>
                    <p class="text-2xl font-bold text-gray-800" id="totalAppointmentsCount">0</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">المواعيد المكتملة</p>
                    <p class="text-2xl font-bold text-gray-800" id="completedAppointmentsCount">0</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">المواعيد الملغاة</p>
                    <p class="text-2xl font-bold text-gray-800" id="cancelledAppointmentsCount">0</p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">الإجراءات السريعة</h3>
            <div class="space-y-3">
                <a href="/admin/dashboard/appointments?search=${patientId}" class="block w-full px-4 py-3 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition text-center">
                    <i class="fas fa-calendar ml-2"></i>عرض المواعيد
                </a>
                <button onclick="resetPassword()" class="block w-full px-4 py-3 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition text-center">
                    <i class="fas fa-key ml-2"></i>إعادة تعيين كلمة المرور
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
const patientId = window.location.pathname.split('/').pop();

window.addEventListener('load', async function() {
    await loadPatientDetails();
});

async function loadPatientDetails() {
    try {
        showLoading();
        
        const data = await apiCall(`/admin/dashboard/patients/${patientId}`);
        
        if (data.success) {
            renderPatientDetails(data.data);
        } else {
            alert(data.error?.message || 'فشل تحميل البيانات');
        }
    } catch (error) {
        console.error('Error loading patient details:', error);
        alert('حدث خطأ أثناء تحميل البيانات');
    } finally {
        hideLoading();
    }
}

function renderPatientDetails(patient) {
    // Basic Info
    document.getElementById('patientName').textContent = patient.name || 'غير محدد';
    document.getElementById('patientPhone').textContent = patient.phone || '-';
    document.getElementById('patientType').textContent = patient.is_ghost ? 'Ghost' : 'عادي';
    document.getElementById('patientPhoneFull').textContent = patient.phone || '-';
    document.getElementById('patientEmail').textContent = patient.email || '-';
    document.getElementById('patientJoinedDate').textContent = formatDate(patient.created_at);

    // Status
    const statusBadge = document.getElementById('patientStatusBadge');
    statusBadge.className = `px-4 py-2 rounded-full text-sm font-semibold ${patient.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
    statusBadge.textContent = patient.status === 'active' ? 'نشط' : 'محظور';

    // Action Buttons
    const actionButtons = document.getElementById('actionButtons');
    let buttons = '';
    
    if (patient.status === 'active') {
        buttons += `
            <button onclick="blockPatient()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-ban ml-2"></i>حظر
            </button>
        `;
    } else {
        buttons += `
            <button onclick="unblockPatient()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-check ml-2"></i>إلغاء الحظر
            </button>
        `;
    }
    
    buttons += `
        <button onclick="deletePatient()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
            <i class="fas fa-trash ml-2"></i>حذف
        </button>
    `;
    
    actionButtons.innerHTML = buttons;

    // Statistics
    document.getElementById('totalAppointmentsCount').textContent = patient.total_appointments || 0;
    document.getElementById('completedAppointmentsCount').textContent = patient.completed_appointments || 0;
    document.getElementById('cancelledAppointmentsCount').textContent = patient.cancelled_appointments || 0;

    // Recent Appointments
    renderRecentAppointments(patient.recent_appointments || []);
}

function renderRecentAppointments(appointments) {
    const recentAppointments = document.getElementById('recentAppointments');
    
    if (appointments.length === 0) {
        recentAppointments.innerHTML = '<p class="text-gray-500">لا توجد مواعيد</p>';
        return;
    }

    recentAppointments.innerHTML = appointments.map(appointment => `
        <div class="p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-semibold text-gray-800">${appointment.doctor_name || 'غير محدد'}</p>
                    <p class="text-sm text-gray-600">${appointment.speciality || '-'}</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-semibold ${getStatusClass(appointment.status)}">
                    ${getStatusText(appointment.status)}
                </span>
            </div>
            <p class="text-sm text-gray-600 mt-2">${formatDate(appointment.appointment_date)} - ${appointment.appointment_time || '-'}</p>
        </div>
    `).join('');
}

async function blockPatient() {
    if (!confirm('هل أنت متأكد من حظر هذا المريض؟')) return;

    try {
        const data = await apiCall(`/admin/dashboard/patients/${patientId}/block`, {
            method: 'POST'
        });

        if (data.success) {
            alert('تم الحظر بنجاح');
            loadPatientDetails();
        } else {
            alert(data.error?.message || 'فشلت العملية');
        }
    } catch (error) {
        alert('حدث خطأ أثناء الحظر');
    }
}

async function unblockPatient() {
    if (!confirm('هل أنت متأكد من إلغاء الحظر عن هذا المريض؟')) return;

    try {
        const data = await apiCall(`/admin/dashboard/patients/${patientId}/unblock`, {
            method: 'POST'
        });

        if (data.success) {
            alert('تم إلغاء الحظر بنجاح');
            loadPatientDetails();
        } else {
            alert(data.error?.message || 'فشلت العملية');
        }
    } catch (error) {
        alert('حدث خطأ أثناء إلغاء الحظر');
    }
}

async function deletePatient() {
    if (!confirm('هل أنت متأكد من حذف هذا المريض؟')) return;

    try {
        const data = await apiCall(`/admin/dashboard/patients/${patientId}`, {
            method: 'DELETE'
        });

        if (data.success) {
            alert('تم الحذف بنجاح');
            window.location.href = '/admin/dashboard/patients';
        } else {
            alert(data.error?.message || 'فشلت العملية');
        }
    } catch (error) {
        alert('حدث خطأ أثناء الحذف');
    }
}

async function resetPassword() {
    if (!confirm('هل أنت متأكد من إعادة تعيين كلمة المرور؟')) return;

    try {
        const data = await apiCall(`/admin/dashboard/patients/${patientId}/reset-password`, {
            method: 'POST'
        });

        if (data.success) {
            alert('تم إرسال كلمة المرور الجديدة بنجاح');
        } else {
            alert(data.error?.message || 'فشلت العملية');
        }
    } catch (error) {
        alert('حدث خطأ أثناء إعادة تعيين كلمة المرور');
    }
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
