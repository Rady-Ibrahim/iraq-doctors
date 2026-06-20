@extends('doctor.layout')

@section('title', 'الإعدادات')
@section('page-title', 'الإعدادات')
@section('page-description', 'إدارة إعدادات حسابك والاشتراك')

@section('content')
<!-- Settings Tabs -->
<div class="mb-6">
    <div class="flex gap-4 border-b">
        <button onclick="showTab('profile')" id="tab-profile" class="px-4 py-3 border-b-2 border-teal-600 text-teal-600 font-semibold">
            الملف الشخصي
        </button>
        <button onclick="showTab('schedule')" id="tab-schedule" class="px-4 py-3 border-b-2 border-transparent text-gray-600 hover:text-gray-800">
            الجدول الزمني
        </button>
        <button onclick="showTab('subscription')" id="tab-subscription" class="px-4 py-3 border-b-2 border-transparent text-gray-600 hover:text-gray-800">
            الاشتراك
        </button>
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
            <form id="profileForm" onsubmit="updateProfile(event)">
                <div class="space-y-4">
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
                    <button type="submit" class="w-full px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">
                        حفظ التغييرات
                    </button>
                </div>
            </form>
        </div>

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
    </div>
</div>

<!-- Schedule Tab -->
<div id="content-schedule" class="tab-content hidden">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-6">الجدول الزمني</h3>
        <div class="space-y-4" id="schedulesList">
            <p class="text-gray-500">جاري التحميل...</p>
        </div>
        <button onclick="addSchedule()" class="mt-4 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">
            <i class="fas fa-plus ml-2"></i>إضافة موعد
        </button>
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
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Change Password -->
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

        <!-- Two-Factor Authentication -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-6">المصادقة الثنائية</h3>
            <div class="space-y-4">
                <p class="text-gray-600">تفعيل المصادقة الثنائية يضيف طبقة أمان إضافية لحسابك.</p>
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="font-semibold text-gray-800">المصادقة الثنائية</p>
                        <p class="text-sm text-gray-600" id="twoFactorStatus">غير مفعلة</p>
                    </div>
                    <button onclick="toggleTwoFactor()" id="toggleTwoFactorBtn" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">
                        تفعيل
                    </button>
                </div>
            </div>
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
            document.getElementById('bioAr').value = profile.bio_ar || '';
            document.getElementById('bioEn').value = profile.bio_en || '';
            document.getElementById('experienceYears').value = profile.experience_years || '';
            document.getElementById('consultationFee').value = profile.consultation_fee || '';
        }
    } catch (error) {
        console.error('Error loading profile:', error);
    }
}

async function updateProfile(event) {
    event.preventDefault();
    
    try {
        const data = await apiCall('/doctor/api/profile', {
            method: 'PUT',
            body: JSON.stringify({
                name: document.getElementById('profileName').value,
                phone: document.getElementById('profilePhone').value,
                email: document.getElementById('profileEmail').value,
                address: document.getElementById('profileAddress').value,
            })
        });

        if (data.success) {
            alert('تم تحديث الملف الشخصي بنجاح');
        } else {
            alert(data.error?.message || 'فشل التحديث');
        }
    } catch (error) {
        alert('حدث خطأ أثناء التحديث');
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

async function loadSchedules() {
    try {
        const data = await apiCall('/doctor/api/schedules');
        
        if (data.success) {
            renderSchedules(data.data);
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
                <button onclick="deleteSchedule('${schedule.id}')" class="px-3 py-1 bg-red-100 text-red-600 rounded hover:bg-red-200 transition text-sm">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

function addSchedule() {
    alert('سيتم فتح نموذج إضافة جدول جديد');
}

async function deleteSchedule(scheduleId) {
    if (!confirm('هل أنت متأكد من حذف هذا الجدول؟')) return;

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

    container.innerHTML = `
        <div class="p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="font-semibold text-gray-800">${subscription.plan_name || 'غير محدد'}</p>
                    <p class="text-sm text-gray-600">${subscription.price || 0} د.ع / شهر</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-semibold ${subscription.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                    ${subscription.status === 'active' ? 'نشط' : 'غير نشط'}
                </span>
            </div>
            <div class="space-y-2 text-sm">
                <p class="text-gray-600">
                    <span class="font-semibold">تاريخ البدء:</span>
                    ${formatDate(subscription.start_date)}
                </p>
                <p class="text-gray-600">
                    <span class="font-semibold">تاريخ الانتهاء:</span>
                    ${formatDate(subscription.end_date)}
                </p>
            </div>
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

async function toggleTwoFactor() {
    try {
        const data = await apiCall('/doctor/api/two-factor', {
            method: 'POST'
        });

        if (data.success) {
            alert('تم تغيير إعدادات المصادقة الثنائية بنجاح');
            loadTwoFactorStatus();
        } else {
            alert(data.error?.message || 'فشلت العملية');
        }
    } catch (error) {
        alert('حدث خطأ أثناء تغيير الإعدادات');
    }
}

async function loadTwoFactorStatus() {
    try {
        const data = await apiCall('/doctor/api/two-factor-status');
        
        if (data.success) {
            const isEnabled = data.data.enabled;
            document.getElementById('twoFactorStatus').textContent = isEnabled ? 'مفعلة' : 'غير مفعلة';
            document.getElementById('toggleTwoFactorBtn').textContent = isEnabled ? 'إلغاء التفعيل' : 'تفعيل';
        }
    } catch (error) {
        console.error('Error loading two-factor status:', error);
    }
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-IQ');
}
</script>
@endsection
