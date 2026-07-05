@extends('pharmacy.layout')

@section('title', 'إعدادات الصيدلية')
@section('page-title', 'إعدادات الصيدلية')
@section('page-description', 'الملف الشخصي، ساعات العمل، وخدمة التوصيل')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-6">معلومات الصيدلية</h3>
        <form id="profileForm" onsubmit="saveProfile(event)" class="space-y-4" enctype="multipart/form-data">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">اسم الصيدلية</label>
                <input type="text" id="name" name="name" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500">
                <span class="text-red-500 text-sm" data-error-for="name"></span>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">المحافظة</label>
                <select id="governorate_id" name="governorate_id" class="w-full px-4 py-2 border rounded-lg">
                    @foreach ($governorates as $governorate)
                        <option value="{{ $governorate->id }}">{{ $governorate->name_ar }}</option>
                    @endforeach
                </select>
                <span class="text-red-500 text-sm" data-error-for="governorate_id"></span>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">المنطقة</label>
                <input type="text" id="district" name="district" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">العنوان</label>
                <textarea id="address" name="address" rows="2" class="w-full px-4 py-2 border rounded-lg"></textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">الوصف</label>
                <textarea id="description_ar" name="description_ar" rows="3" class="w-full px-4 py-2 border rounded-lg"></textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">هاتف التواصل</label>
                <input type="text" id="contact_phone" name="contact_phone" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">واتساب</label>
                <input type="text" id="whatsapp" name="whatsapp" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">الشعار</label>
                <input type="file" id="logo" name="logo" accept="image/*" class="w-full px-4 py-2 border rounded-lg">
                <img id="logoPreview" src="" alt="" class="mt-2 h-16 hidden rounded">
            </div>
            <button type="submit" class="w-full py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">حفظ الإعدادات</button>
        </form>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">خدمة التوصيل</h3>
            <label class="flex items-center gap-2 mb-4">
                <input type="checkbox" id="delivery_enabled" class="rounded text-emerald-600">
                <span class="text-sm text-gray-700">تفعيل خدمة التوصيل</span>
            </label>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">رسوم التوصيل (د.ع)</label>
                    <input type="number" id="delivery_fee" min="0" class="w-full px-4 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">الحد الأدنى للطلب للتوصيل (د.ع)</label>
                    <input type="number" id="min_order_for_delivery" min="0" class="w-full px-4 py-2 border rounded-lg">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">ساعات العمل</h3>
            <div id="workingHours" class="space-y-3"></div>
            <button type="button" onclick="saveWorkingHours()" class="mt-4 w-full py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">حفظ ساعات العمل</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const dayLabels = {
    saturday: 'السبت', sunday: 'الأحد', monday: 'الإثنين', tuesday: 'الثلاثاء',
    wednesday: 'الأربعاء', thursday: 'الخميس', friday: 'الجمعة',
};
let profileData = {};

window.addEventListener('load', loadProfile);

async function loadProfile() {
    const data = await apiCall('/pharmacy/api/profile');
    if (!data?.success) return;
    profileData = data.data;
    document.getElementById('name').value = profileData.name || '';
    document.getElementById('governorate_id').value = profileData.governorate_id || '';
    document.getElementById('district').value = profileData.district || '';
    document.getElementById('address').value = profileData.address || '';
    document.getElementById('description_ar').value = profileData.description_ar || '';
    document.getElementById('contact_phone').value = profileData.contact_phone || '';
    document.getElementById('whatsapp').value = profileData.whatsapp || '';
    document.getElementById('delivery_enabled').checked = !!profileData.delivery_enabled;
    document.getElementById('delivery_fee').value = profileData.delivery_fee || '';
    document.getElementById('min_order_for_delivery').value = profileData.min_order_for_delivery || '';
    if (profileData.logo) {
        const img = document.getElementById('logoPreview');
        img.src = profileData.logo;
        img.classList.remove('hidden');
    }
    renderWorkingHours(profileData.working_hours || {});
}

function renderWorkingHours(hours) {
    const container = document.getElementById('workingHours');
    container.innerHTML = Object.keys(dayLabels).map(day => {
        const h = hours[day] || { enabled: day !== 'friday', open: '08:00', close: '22:00' };
        return `
            <div class="flex flex-wrap items-center gap-2 border rounded-lg p-3">
                <label class="flex items-center gap-2 w-28">
                    <input type="checkbox" data-day="${day}" class="wh-enabled rounded text-emerald-600" ${h.enabled ? 'checked' : ''}>
                    <span class="text-sm">${dayLabels[day]}</span>
                </label>
                <input type="time" data-day="${day}" class="wh-open px-2 py-1 border rounded text-sm" value="${h.open || '08:00'}">
                <span class="text-gray-400">—</span>
                <input type="time" data-day="${day}" class="wh-close px-2 py-1 border rounded text-sm" value="${h.close || '22:00'}">
            </div>`;
    }).join('');
}

function collectWorkingHours() {
    const hours = {};
    Object.keys(dayLabels).forEach(day => {
        hours[day] = {
            enabled: document.querySelector(`.wh-enabled[data-day="${day}"]`)?.checked || false,
            open: document.querySelector(`.wh-open[data-day="${day}"]`)?.value || '08:00',
            close: document.querySelector(`.wh-close[data-day="${day}"]`)?.value || '22:00',
        };
    });
    return hours;
}

async function saveProfile(e) {
    e.preventDefault();
    clearFieldErrors();
    const formData = new FormData(document.getElementById('profileForm'));
    formData.append('delivery_enabled', document.getElementById('delivery_enabled').checked ? '1' : '0');
    formData.append('delivery_fee', document.getElementById('delivery_fee').value || '0');
    formData.append('min_order_for_delivery', document.getElementById('min_order_for_delivery').value || '0');
    formData.append('working_hours', JSON.stringify(collectWorkingHours()));
    try {
        const data = await apiCall('/pharmacy/api/profile', { method: 'POST', body: formData });
        if (data?.success) alert(data.message || 'تم الحفظ');
        else handleApiError(data);
    } catch (err) {
        handleApiError(err);
    }
}

async function saveWorkingHours() {
    clearFieldErrors();
    const formData = new FormData();
    formData.append('working_hours', JSON.stringify(collectWorkingHours()));
    formData.append('delivery_enabled', document.getElementById('delivery_enabled').checked ? '1' : '0');
    formData.append('delivery_fee', document.getElementById('delivery_fee').value || '0');
    formData.append('min_order_for_delivery', document.getElementById('min_order_for_delivery').value || '0');
    const data = await apiCall('/pharmacy/api/profile', { method: 'POST', body: formData });
    if (data?.success) alert('تم حفظ ساعات العمل');
}
</script>
@endsection
