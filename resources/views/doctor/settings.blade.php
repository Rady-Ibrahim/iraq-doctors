@extends('doctor.layout')

@section('title', 'الإعدادات')
@section('page-title', 'الإعدادات')
@section('page-description', 'إدارة إعدادات حسابك والاشتراك')

@section('content')
@php
    $isOwner = $isDoctorOwner ?? auth('web')->user()?->isDoctor() ?? false;
@endphp
<!-- Settings Tabs -->
<div class="mb-6">
    <div class="flex gap-4 border-b flex-wrap">
        <button onclick="showTab('profile')" id="tab-profile" class="px-4 py-3 border-b-2 border-teal-600 text-teal-600 font-semibold">
            الملف الشخصي
        </button>
        @if ($isOwner)
        <a href="/doctor/dashboard/staff" id="tab-staff" class="px-4 py-3 border-b-2 border-transparent text-gray-600 hover:text-gray-800">
            فريق العيادة
        </a>
        <button onclick="showTab('schedule')" id="tab-schedule" class="px-4 py-3 border-b-2 border-transparent text-gray-600 hover:text-gray-800">
            الجدول الزمني
        </button>
        <button onclick="showTab('subscription')" id="tab-subscription" class="px-4 py-3 border-b-2 border-transparent text-gray-600 hover:text-gray-800">
            الاشتراك
        </button>
        @endif
        <button onclick="showTab('security')" id="tab-security" class="px-4 py-3 border-b-2 border-transparent text-gray-600 hover:text-gray-800">
            الأمان
        </button>
    </div>
</div>

<!-- Profile Tab -->
<div id="content-profile" class="tab-content">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Profile Form -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-6">معلومات شخصية</h3>
            <form id="profileForm" onsubmit="updateProfile(event)" enctype="multipart/form-data">
                <div class="space-y-4">
                    {{-- Avatar --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">الصورة الشخصية</label>
                        <div class="flex items-center gap-4">
                            <div id="avatarPreviewWrap" class="w-16 h-16 rounded-full bg-teal-100 flex items-center justify-center overflow-hidden flex-shrink-0">
                                <i id="avatarIcon" class="fas fa-user text-teal-500 text-2xl"></i>
                                <img id="avatarPreview" src="" alt="" class="hidden w-full h-full object-cover">
                            </div>
                            <div class="flex-1">
                                <input type="file" id="profileAvatar" name="avatar" accept=".jpg,.jpeg,.png"
                                    onchange="previewAvatar(this)"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500">
                                <p class="text-xs text-gray-400 mt-1">JPG أو PNG — حتى 5 ميجا</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">الاسم</label>
                        <input type="text" id="profileName" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">الهاتف</label>
                        <input type="tel" id="profilePhone"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">البريد الإلكتروني</label>
                        <input type="email" id="profileEmail"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">العنوان</label>
                        <textarea id="profileAddress" rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">موقع العيادة على الخريطة</label>
                        <p class="text-xs text-gray-500 mb-2">اضغط على الخريطة أو اسحب العلامة لتحديث الموقع</p>
                        <button type="button" onclick="detectSettingsLocation()" id="detectSettingsBtnDoc"
                            class="mb-2 flex items-center gap-2 px-3 py-1.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition text-sm">
                            <i class="fas fa-location-arrow"></i>
                            <span>تحديد موقعي الحالي</span>
                        </button>
                        <div id="clinic-map" class="border border-gray-300 mb-3"></div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">خط العرض</label>
                                <input type="number" step="any" id="profileLatitude" readonly
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">خط الطول</label>
                                <input type="number" step="any" id="profileLongitude" readonly
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">
                        حفظ التغييرات
                    </button>
                </div>
            </form>
        </div>

        @if ($isOwner)
        <!-- Professional Info -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-6">معلومات مهنية</h3>
            <form id="professionalForm" onsubmit="updateProfessional(event)">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">السيرة الذاتية (عربي)</label>
                        <textarea id="bioAr" rows="4"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">السيرة الذاتية (إنجليزي)</label>
                        <textarea id="bioEn" rows="4"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">سنوات الخبرة</label>
                        <input type="number" id="experienceYears" min="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">سعر الكشف</label>
                        <input type="number" id="consultationFee" min="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">
                        حفظ التغييرات
                    </button>
                </div>
            </form>
        </div>
        @endif
    </div>
</div>

<!-- Schedule Tab -->
<div id="content-schedule" class="tab-content hidden">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-6">الجدول الزمني</h3>
        <div class="space-y-4" id="schedulesList">
            <p class="text-gray-500">جاري التحميل...</p>
        </div>
        <button onclick="openScheduleModal()" class="mt-4 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">
            <i class="fas fa-plus ml-2"></i>إضافة موعد
        </button>
    </div>
</div>

<!-- Add/Edit Schedule Modal -->
<div id="scheduleModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="p-6 border-b flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800" id="scheduleModalTitle">إضافة موعد</h3>
            <button onclick="closeScheduleModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="scheduleForm" onsubmit="saveSchedule(event)" class="p-6 space-y-4">
            <input type="hidden" id="scheduleId" value="">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">اليوم</label>
                <select id="scheduleDay" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                    <option value="">اختر اليوم</option>
                    <option value="saturday">السبت</option>
                    <option value="sunday">الأحد</option>
                    <option value="monday">الاثنين</option>
                    <option value="tuesday">الثلاثاء</option>
                    <option value="wednesday">الأربعاء</option>
                    <option value="thursday">الخميس</option>
                    <option value="friday">الجمعة</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">وقت البدء</label>
                    <input type="time" id="scheduleStartTime" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">وقت الانتهاء</label>
                    <input type="time" id="scheduleEndTime" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                </div>
            </div>
            <p data-error-for="day_of_week" class="text-red-500 text-sm"></p>
            <p data-error-for="start_time" class="text-red-500 text-sm"></p>
            <p data-error-for="end_time" class="text-red-500 text-sm"></p>
            <button type="submit" class="w-full py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">حفظ</button>
        </form>
    </div>
</div>

<!-- Subscription Tab -->
<div id="content-subscription" class="tab-content hidden">
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-6">اشتراكك الحالي</h3>
        <div id="subscriptionInfo" class="space-y-4">
            <p class="text-gray-500">جاري التحميل...</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-6">خطط الاشتراك</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="subscriptionPlans">
            <!-- Plans will be loaded here -->
        </div>
    </div>
</div>

<!-- Security Tab -->
<div id="content-security" class="tab-content hidden">
    <div class="max-w-lg">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-6">تغيير كلمة المرور</h3>
            <form id="passwordForm" onsubmit="changePassword(event)">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">كلمة المرور الحالية</label>
                        <input type="password" id="currentPassword"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">كلمة المرور الجديدة</label>
                        <input type="password" id="newPassword"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">تأكيد كلمة المرور الجديدة</label>
                        <input type="password" id="confirmPassword"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">
                        تغيير كلمة المرور
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
window.addEventListener('load', async function() {
    await loadProfile();
    await loadSchedules();
    await loadSubscription();
});

function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });
    
    // Remove active state from all tab buttons
    document.querySelectorAll('[id^="tab-"]').forEach(btn => {
        btn.classList.remove('border-teal-600', 'text-teal-600');
        btn.classList.add('border-transparent', 'text-gray-600');
    });
    
    // Show selected tab
    document.getElementById(`content-${tabName}`).classList.remove('hidden');
    
    // Add active state to selected tab button
    const activeBtn = document.getElementById(`tab-${tabName}`);
    activeBtn.classList.remove('border-transparent', 'text-gray-600');
    activeBtn.classList.add('border-teal-600', 'text-teal-600');
}

async function loadProfile() {
    try {
        const data = await apiCall('/doctor/api/profile');
        
        if (data.success) {
            const profile = data.data;
            document.getElementById('profileName').value = profile.name || '';
            document.getElementById('profilePhone').value = profile.phone || '';
            document.getElementById('profileEmail').value = profile.email || '';
            document.getElementById('profileAddress').value = profile.address || '';
            document.getElementById('profileLatitude').value = profile.latitude || '33.3152';
            document.getElementById('profileLongitude').value = profile.longitude || '44.3661';
            const bioAr = document.getElementById('bioAr');
            const bioEn = document.getElementById('bioEn');
            if (bioAr) bioAr.value = profile.bio_ar || '';
            if (bioEn) bioEn.value = profile.bio_en || '';
            const exp = document.getElementById('experienceYears');
            const fee = document.getElementById('consultationFee');
            if (exp) exp.value = profile.experience_years || '';
            if (fee) fee.value = profile.consultation_fee || '';

            // Show current avatar
            if (profile.avatar) {
                const img = document.getElementById('avatarPreview');
                const icon = document.getElementById('avatarIcon');
                img.src = profile.avatar;
                img.classList.remove('hidden');
                icon.classList.add('hidden');
            }

            initClinicMap();
        }
    } catch (error) {
        console.error('Error loading profile:', error);
    }
}

let clinicMap = null;
let clinicMarker = null;

function initClinicMap() {
    const latInput = document.getElementById('profileLatitude');
    const lngInput = document.getElementById('profileLongitude');
    if (!latInput || !lngInput || typeof L === 'undefined') return;

    const lat = parseFloat(latInput.value) || 33.3152;
    const lng = parseFloat(lngInput.value) || 44.3661;

    if (clinicMap) {
        clinicMap.setView([lat, lng], clinicMap.getZoom());
        clinicMarker.setLatLng([lat, lng]);
        return;
    }

    clinicMap = L.map('clinic-map').setView([lat, lng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(clinicMap);

    clinicMarker = L.marker([lat, lng], { draggable: true }).addTo(clinicMap);

    function updateCoords(latVal, lngVal) {
        latInput.value = latVal.toFixed(7);
        lngInput.value = lngVal.toFixed(7);
    }

    clinicMap.on('click', function (e) {
        clinicMarker.setLatLng(e.latlng);
        updateCoords(e.latlng.lat, e.latlng.lng);
    });

    clinicMarker.on('dragend', function () {
        const pos = clinicMarker.getLatLng();
        updateCoords(pos.lat, pos.lng);
    });

    setTimeout(() => clinicMap.invalidateSize(), 200);
}

function detectSettingsLocation() {
    const btn = document.getElementById('detectSettingsBtnDoc');
    if (!navigator.geolocation) { alert('المتصفح لا يدعم تحديد الموقع'); return; }
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التحديد...';
    navigator.geolocation.getCurrentPosition(
        function(pos) {
            const lat = pos.coords.latitude, lng = pos.coords.longitude;
            const latInput = document.getElementById('profileLatitude');
            const lngInput = document.getElementById('profileLongitude');
            latInput.value = lat.toFixed(7);
            lngInput.value = lng.toFixed(7);
            if (clinicMarker) clinicMarker.setLatLng([lat, lng]);
            if (clinicMap) clinicMap.setView([lat, lng], 16);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-location-arrow"></i> تحديد موقعي الحالي';
        },
        function() {
            alert('تعذر تحديد الموقع. تأكد من منح الإذن للمتصفح.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-location-arrow"></i> تحديد موقعي الحالي';
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

async function updateProfile(event) {
    event.preventDefault();

    const formData = new FormData();
    formData.append('name',      document.getElementById('profileName').value);
    formData.append('phone',     document.getElementById('profilePhone').value);
    formData.append('email',     document.getElementById('profileEmail').value);
    formData.append('address',   document.getElementById('profileAddress').value);
    formData.append('latitude',  document.getElementById('profileLatitude').value);
    formData.append('longitude', document.getElementById('profileLongitude').value);

    const avatarFile = document.getElementById('profileAvatar').files[0];
    if (avatarFile) formData.append('avatar', avatarFile);

    try {
        // Use apiUpload (not apiCall) — preserves multipart/form-data boundary
        const data = await apiUpload('/doctor/api/profile/avatar', formData, 'POST');

        if (data && data.success) {
            showSuccess('تم تحديث الملف الشخصي بنجاح');
            if (data.data?.avatar) {
                const img  = document.getElementById('avatarPreview');
                const icon = document.getElementById('avatarIcon');
                img.src = data.data.avatar;
                img.classList.remove('hidden');
                icon.classList.add('hidden');
            }
        } else {
            showError(data?.error?.message || 'فشل التحديث');
        }
    } catch (error) {
        showError('حدث خطأ أثناء التحديث');
    }
}

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById('avatarPreview');
            const icon = document.getElementById('avatarIcon');
            img.src = e.target.result;
            img.classList.remove('hidden');
            icon.classList.add('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

async function updateProfessional(event) {
    event.preventDefault();
    
    try {
        const data = await apiCall('/doctor/api/professional', {
            method: 'PUT',
            body: JSON.stringify({
                bio_ar: document.getElementById('bioAr').value,
                bio_en: document.getElementById('bioEn').value,
                experience_years: document.getElementById('experienceYears').value,
                consultation_fee: document.getElementById('consultationFee').value,
            })
        });

        if (data.success) {
            alert('تم تحديث المعلومات المهنية بنجاح');
        } else {
            alert(data.error?.message || 'فشل التحديث');
        }
    } catch (error) {
        alert('حدث خطأ أثناء التحديث');
    }
}

let schedulesData = [];

async function loadSchedules() {
    try {
        const data = await apiCall('/doctor/api/schedules');
        
        if (data.success) {
            schedulesData = data.data || [];
            renderSchedules(schedulesData);
        }
    } catch (error) {
        console.error('Error loading schedules:', error);
    }
}

function renderSchedules(schedules) {
    const container = document.getElementById('schedulesList');
    
    if (schedules.length === 0) {
        container.innerHTML = '<p class="text-gray-500">لا توجد جداول</p>';
        return;
    }

    const daysAr = {
        'Monday': 'الاثنين',
        'Tuesday': 'الثلاثاء',
        'Wednesday': 'الأربعاء',
        'Thursday': 'الخميس',
        'Friday': 'الجمعة',
        'Saturday': 'السبت',
        'Sunday': 'الأحد'
    };

    container.innerHTML = schedules.map(schedule => `
        <div class="p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-semibold text-gray-800">${daysAr[schedule.day_of_week] || schedule.day_of_week}</p>
                    <p class="text-sm text-gray-600">${schedule.start_time} - ${schedule.end_time}</p>
                </div>
                <div class="flex gap-2">
                    <button onclick="editSchedule('${schedule.id}')" class="px-3 py-1 bg-teal-100 text-teal-600 rounded hover:bg-teal-200 transition text-sm">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteSchedule('${schedule.id}')" class="px-3 py-1 bg-red-100 text-red-600 rounded hover:bg-red-200 transition text-sm">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function openScheduleModal() {
    document.getElementById('scheduleModalTitle').textContent = 'إضافة موعد';
    document.getElementById('scheduleId').value = '';
    document.getElementById('scheduleForm').reset();
    document.getElementById('scheduleModal').classList.remove('hidden');
    document.getElementById('scheduleModal').classList.add('flex');
}

function editSchedule(scheduleId) {
    const schedule = schedulesData.find(s => s.id == scheduleId);
    if (!schedule) return;

    document.getElementById('scheduleModalTitle').textContent = 'تعديل الموعد';
    document.getElementById('scheduleId').value = schedule.id;
    document.getElementById('scheduleDay').value = schedule.day_of_week.toLowerCase();
    document.getElementById('scheduleStartTime').value = schedule.start_time;
    document.getElementById('scheduleEndTime').value = schedule.end_time;
    document.getElementById('scheduleModal').classList.remove('hidden');
    document.getElementById('scheduleModal').classList.add('flex');
}

function closeScheduleModal() {
    document.getElementById('scheduleModal').classList.add('hidden');
    document.getElementById('scheduleModal').classList.remove('flex');
}

async function saveSchedule(event) {
    event.preventDefault();

    const scheduleId = document.getElementById('scheduleId').value;
    const payload = {
        day_of_week: document.getElementById('scheduleDay').value,
        start_time: document.getElementById('scheduleStartTime').value,
        end_time: document.getElementById('scheduleEndTime').value,
    };

    if (!payload.day_of_week || !payload.start_time || !payload.end_time) {
        showError('يرجى إكمال جميع الحقول');
        return;
    }

    if (payload.start_time >= payload.end_time) {
        showError('يجب أن يكون وقت الانتهاء بعد وقت البدء');
        return;
    }

    showLoading();
    const endpoint = scheduleId ? `/doctor/api/schedules/${scheduleId}` : '/doctor/api/schedules';
    const data = scheduleId
        ? await apiCall(endpoint, { method: 'PUT', body: JSON.stringify(payload) })
        : await apiCall(endpoint, { method: 'POST', body: JSON.stringify(payload) });
    hideLoading();

    if (!data?.success) {
        showError(data?.error?.message || data?.message || 'فشل حفظ الموعد');
        return;
    }

    showSuccess(data.message || 'تم الحفظ بنجاح');
    closeScheduleModal();
    schedulesData = data.data || [];
    renderSchedules(schedulesData);
}

async function deleteSchedule(scheduleId) {
    if (!await confirmAction('هل أنت متأكد من حذف هذا الجدول؟')) return;

    try {
        const data = await apiCall(`/doctor/api/schedules/${scheduleId}`, {
            method: 'DELETE'
        });

        if (data.success) {
            alert('تم الحذف بنجاح');
            loadSchedules();
        } else {
            alert(data.error?.message || 'فشلت العملية');
        }
    } catch (error) {
        alert('حدث خطأ أثناء الحذف');
    }
}

async function loadSubscription() {
    try {
        const data = await apiCall('/doctor/api/subscription');
        
        if (data.success) {
            renderSubscription(data.data);
        }
    } catch (error) {
        console.error('Error loading subscription:', error);
    }
}

function renderSubscription(subscription) {
    const container = document.getElementById('subscriptionInfo');
    
    if (!subscription) {
        container.innerHTML = `
            <p class="text-gray-600">ليس لديك اشتراك نشط حالياً</p>
            <a href="/doctor/dashboard/subscription/plans" class="inline-block mt-4 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">
                عرض الخطط المتاحة
            </a>
        `;
        return;
    }

    const statusLabels = { active: 'نشط', pending_payment: 'بانتظار الدفع' };
    const statusColors = { active: 'bg-green-100 text-green-800', pending_payment: 'bg-yellow-100 text-yellow-800' };

    container.innerHTML = `
        <div class="p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="font-semibold text-gray-800">${subscription.plan_name || 'غير محدد'}</p>
                    <p class="text-sm text-gray-600">${subscription.price || 0} د.ع</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-semibold ${statusColors[subscription.status] || 'bg-red-100 text-red-800'}">
                    ${statusLabels[subscription.status] || 'غير نشط'}
                </span>
            </div>
            ${subscription.status === 'active' ? `<div class="space-y-2 text-sm">
                <p class="text-gray-600"><span class="font-semibold">تاريخ البدء:</span> ${formatDate(subscription.start_date)}</p>
                <p class="text-gray-600"><span class="font-semibold">تاريخ الانتهاء:</span> ${formatDate(subscription.end_date)}</p>
            </div>` : subscription.status === 'pending_payment' ? '<p class="text-sm text-yellow-700">طلبك قيد المراجعة من الإدارة</p>' : ''}
            <a href="/doctor/dashboard/subscription/plans" class="inline-block mt-4 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition text-sm">
                ${subscription.status === 'active' ? 'تغيير الباقة' : 'عرض الخطط'}
            </a>
        </div>
    `;
}

async function changePassword(event) {
    event.preventDefault();
    
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (newPassword !== confirmPassword) {
        alert('كلمات المرور غير متطابقة');
        return;
    }

    try {
        const data = await apiCall('/doctor/api/change-password', {
            method: 'POST',
            body: JSON.stringify({
                current_password: document.getElementById('currentPassword').value,
                new_password: newPassword,
            })
        });

        if (data.success) {
            alert('تم تغيير كلمة المرور بنجاح');
            document.getElementById('passwordForm').reset();
        } else {
            alert(data.error?.message || 'فشل تغيير كلمة المرور');
        }
    } catch (error) {
        alert('حدث خطأ أثناء تغيير كلمة المرور');
    }
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-IQ');
}
</script>
@endsection
