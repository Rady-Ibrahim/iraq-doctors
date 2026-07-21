@extends('pharmacy.layout')

@section('title', 'إعدادات الصيدلية')
@section('page-title', 'إعدادات الصيدلية')
@section('page-description', 'الملف الشخصي، ساعات العمل، وخدمة التوصيل')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Profile form --}}
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
                <label class="block text-sm font-semibold text-gray-700 mb-2">موقع الصيدلية</label>
                <button type="button" onclick="detectSettingsLocation()" id="detectSettingsBtn"
                    class="mb-2 flex items-center gap-2 px-3 py-1.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm">
                    <i class="fas fa-location-arrow"></i>
                    <span>تحديد موقعي الحالي</span>
                </button>
                <div id="location-map" class="border border-gray-300 mb-3" style="height:200px;border-radius:.5rem;z-index:0;"></div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">خط العرض</label>
                        <input type="number" step="any" id="latitude" name="latitude" readonly class="w-full px-3 py-2 border rounded-lg bg-gray-50 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">خط الطول</label>
                        <input type="number" step="any" id="longitude" name="longitude" readonly class="w-full px-3 py-2 border rounded-lg bg-gray-50 text-sm">
                    </div>
                </div>
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

    {{-- Right column --}}
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
                    <label class="block text-sm font-semibold text-gray-700 mb-2">الحد الأدنى للتوصيل (د.ع)</label>
                    <input type="number" id="min_order_for_delivery" min="0" class="w-full px-4 py-2 border rounded-lg">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">ساعات العمل</h3>
            <div id="workingHours" class="space-y-3"></div>
            <button type="button" onclick="saveWorkingHours()"
                class="mt-4 w-full py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                حفظ ساعات العمل
            </button>
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
let locationMap = null, locationMarker = null;

window.addEventListener('load', loadProfile);

async function loadProfile() {
    const data = await apiCall('/pharmacy/api/profile');
    if (!data?.success) return;
    profileData = data.data;
    document.getElementById('name').value                   = profileData.name              || '';
    document.getElementById('governorate_id').value        = profileData.governorate_id    || '';
    document.getElementById('district').value               = profileData.district          || '';
    document.getElementById('address').value                = profileData.address           || '';
    document.getElementById('latitude').value               = profileData.latitude          || '33.3152';
    document.getElementById('longitude').value              = profileData.longitude         || '44.3661';
    document.getElementById('description_ar').value         = profileData.description_ar    || '';
    document.getElementById('contact_phone').value          = profileData.contact_phone     || '';
    document.getElementById('whatsapp').value               = profileData.whatsapp          || '';
    document.getElementById('delivery_enabled').checked     = !!profileData.delivery_enabled;
    document.getElementById('delivery_fee').value           = profileData.delivery_fee      || '';
    document.getElementById('min_order_for_delivery').value = profileData.min_order_for_delivery || '';
    if (profileData.logo) {
        const img = document.getElementById('logoPreview');
        img.src = profileData.logo; img.classList.remove('hidden');
    }
    renderWorkingHours(profileData.working_hours || {});
    initLocationMap();
}

async function saveProfile(e) {
    e.preventDefault(); clearFieldErrors();
    const formData = new FormData(document.getElementById('profileForm'));
    formData.append('delivery_enabled',       document.getElementById('delivery_enabled').checked ? '1' : '0');
    formData.append('delivery_fee',           document.getElementById('delivery_fee').value || '0');
    formData.append('min_order_for_delivery', document.getElementById('min_order_for_delivery').value || '0');
    formData.append('working_hours',          JSON.stringify(collectWorkingHours()));
    try {
        const data = await apiCall('/pharmacy/api/profile', { method: 'POST', body: formData });
        if (data?.success) showSuccess(data.message || 'تم الحفظ');
        else handleApiError(data);
    } catch (err) { handleApiError(err); }
}

function initLocationMap() {
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    if (!latInput || typeof L === 'undefined') return;
    const lat = parseFloat(latInput.value) || 33.3152;
    const lng = parseFloat(lngInput.value) || 44.3661;
    if (locationMap) { locationMap.setView([lat,lng]); locationMarker.setLatLng([lat,lng]); return; }
    locationMap = L.map('location-map').setView([lat, lng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(locationMap);
    locationMarker = L.marker([lat, lng], { draggable: true }).addTo(locationMap);
    function updateCoords(la, lo) { latInput.value = la.toFixed(7); lngInput.value = lo.toFixed(7); }
    locationMap.on('click', e => { locationMarker.setLatLng(e.latlng); updateCoords(e.latlng.lat, e.latlng.lng); });
    locationMarker.on('dragend', () => { const p = locationMarker.getLatLng(); updateCoords(p.lat, p.lng); });
    setTimeout(() => locationMap.invalidateSize(), 200);
}

function detectSettingsLocation() {
    const btn = document.getElementById('detectSettingsBtn');
    if (!navigator.geolocation) { alert('المتصفح لا يدعم تحديد الموقع'); return; }
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التحديد...';
    navigator.geolocation.getCurrentPosition(
        function(pos) {
            const lat = pos.coords.latitude, lng = pos.coords.longitude;
            document.getElementById('latitude').value  = lat.toFixed(7);
            document.getElementById('longitude').value = lng.toFixed(7);
            if (locationMarker) locationMarker.setLatLng([lat, lng]);
            if (locationMap)    locationMap.setView([lat, lng], 16);
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
                <input type="time" data-day="${day}" class="wh-open  px-2 py-1 border rounded text-sm" value="${h.open  || '08:00'}">
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
            open:    document.querySelector(`.wh-open[data-day="${day}"]`)?.value  || '08:00',
            close:   document.querySelector(`.wh-close[data-day="${day}"]`)?.value || '22:00',
        };
    });
    return hours;
}

async function saveWorkingHours() {
    const formData = new FormData();
    formData.append('working_hours',          JSON.stringify(collectWorkingHours()));
    formData.append('delivery_enabled',       document.getElementById('delivery_enabled').checked ? '1' : '0');
    formData.append('delivery_fee',           document.getElementById('delivery_fee').value || '0');
    formData.append('min_order_for_delivery', document.getElementById('min_order_for_delivery').value || '0');
    const data = await apiCall('/pharmacy/api/profile', { method: 'POST', body: formData });
    if (data?.success) showSuccess('تم حفظ ساعات العمل');
}
</script>
@endsection
