@extends('pharmacy.layout')

@section('title', 'فروع الصيدلية')
@section('page-title', 'فروع الصيدلية')
@section('page-description', 'إدارة فروع الصيدلية وعناوينها')

@section('content')
<div class="flex justify-between items-center mb-6">
    <p class="text-gray-600">أضف فروع الصيدلية لتظهر للمرضى عند طلب الأدوية.</p>
    <button onclick="openBranchModal()" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
        <i class="fas fa-plus ml-1"></i> إضافة فرع
    </button>
</div>

<div id="branchesList" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <p class="text-gray-500">جاري التحميل...</p>
</div>

<div id="branchModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex justify-between items-center">
            <h3 class="text-lg font-bold" id="branchModalTitle">إضافة فرع</h3>
            <button onclick="closeBranchModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="branchForm" onsubmit="saveBranch(event)" class="p-6 space-y-4">
            <input type="hidden" id="branchId">
            <div>
                <label class="block text-sm font-semibold mb-1">اسم الفرع</label>
                <input type="text" id="branch_name" required class="w-full px-4 py-2 border rounded-lg">
                <span class="text-red-500 text-sm" data-error-for="branch_name"></span>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">المحافظة</label>
                <select id="branch_governorate_id" class="w-full px-4 py-2 border rounded-lg">
                    <option value="">—</option>
                    @foreach ($governorates as $governorate)
                        <option value="{{ $governorate->id }}">{{ $governorate->name_ar }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">المنطقة</label>
                <input type="text" id="branch_district" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">العنوان</label>
                <textarea id="branch_address" rows="2" class="w-full px-4 py-2 border rounded-lg"></textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">موقع الفرع على الخريطة</label>
                <button type="button" onclick="detectBranchLocation()" id="detectBranchBtn"
                    class="mb-2 flex items-center gap-2 px-3 py-1.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm">
                    <i class="fas fa-location-arrow"></i>
                    <span>تحديد موقعي الحالي</span>
                </button>
                <div id="branch-map" style="height:200px;border-radius:0.5rem;z-index:0;" class="border border-gray-300 mb-2"></div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">خط العرض</label>
                        <input type="number" step="any" id="branch_latitude" readonly
                            class="w-full px-3 py-1.5 border rounded-lg bg-gray-50 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">خط الطول</label>
                        <input type="number" step="any" id="branch_longitude" readonly
                            class="w-full px-3 py-1.5 border rounded-lg bg-gray-50 text-sm">
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">الهاتف</label>
                <input type="text" id="branch_phone" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <label class="flex items-center gap-2">
                <input type="checkbox" id="branch_is_primary" class="rounded text-emerald-600">
                <span class="text-sm">فرع رئيسي</span>
            </label>
            <div>
                <label class="block text-sm font-semibold mb-2">ساعات عمل الفرع (اختياري)</label>
                <div id="branchWorkingHours" class="space-y-2 max-h-48 overflow-y-auto"></div>
            </div>
            <button type="submit" class="w-full py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">حفظ</button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const dayLabels = {
    saturday: 'السبت', sunday: 'الأحد', monday: 'الإثنين', tuesday: 'الثلاثاء',
    wednesday: 'الأربعاء', thursday: 'الخميس', friday: 'الجمعة',
};

window.addEventListener('load', loadBranches);

async function loadBranches() {
    const data = await apiCall('/pharmacy/api/branches');
    const list = document.getElementById('branchesList');
    if (!data?.success || !data.data?.length) {
        list.innerHTML = '<p class="text-gray-500 col-span-2">لا توجد فروع بعد. أضف فرعاً جديداً.</p>';
        return;
    }
    list.innerHTML = data.data.map(b => `
        <div class="bg-white rounded-xl shadow-sm p-5 border ${b.is_primary ? 'border-emerald-300' : ''}">
            <div class="flex justify-between items-start mb-2">
                <h4 class="font-bold text-gray-800">${b.branch_name}${b.is_primary ? ' <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded">رئيسي</span>' : ''}</h4>
                <div class="flex gap-2">
                    <button onclick='editBranch(${JSON.stringify(b)})' class="text-emerald-600 text-sm">تعديل</button>
                    ${!b.is_primary ? `<button onclick="deleteBranch(${b.id})" class="text-red-600 text-sm">حذف</button>` : ''}
                </div>
            </div>
            <p class="text-sm text-gray-600">${b.governorate_name || ''} ${b.district || ''}</p>
            <p class="text-sm text-gray-500 mt-1">${b.address || '-'}</p>
            ${b.phone ? `<p class="text-sm text-gray-500 mt-1"><i class="fas fa-phone ml-1"></i>${b.phone}</p>` : ''}
            ${b.working_hours ? `<p class="text-xs text-gray-400 mt-2"><i class="fas fa-clock ml-1"></i>ساعات عمل مخصصة للفرع</p>` : ''}
        </div>
    `).join('');
}

function openBranchModal(branch = null) {
    document.getElementById('branchId').value = branch?.id || '';
    document.getElementById('branch_name').value = branch?.branch_name || '';
    document.getElementById('branch_governorate_id').value = branch?.governorate_id || '';
    document.getElementById('branch_district').value = branch?.district || '';
    document.getElementById('branch_address').value = branch?.address || '';
    document.getElementById('branch_phone').value = branch?.phone || '';
    document.getElementById('branch_is_primary').checked = !!branch?.is_primary;
    document.getElementById('branch_latitude').value = branch?.latitude || '';
    document.getElementById('branch_longitude').value = branch?.longitude || '';
    renderBranchWorkingHours(branch?.working_hours || {});
    document.getElementById('branchModalTitle').textContent = branch ? 'تعديل فرع' : 'إضافة فرع';
    clearFieldErrors();
    document.getElementById('branchModal').classList.remove('hidden');
    document.getElementById('branchModal').classList.add('flex');
    setTimeout(() => initBranchMap(branch?.latitude, branch?.longitude), 150);
}

function editBranch(branch) { openBranchModal(branch); }

function closeBranchModal() {
    document.getElementById('branchModal').classList.add('hidden');
    document.getElementById('branchModal').classList.remove('flex');
    if (branchMap) { branchMap.remove(); branchMap = null; branchMarker = null; }
}

function renderBranchWorkingHours(hours) {
    const container = document.getElementById('branchWorkingHours');
    container.innerHTML = Object.keys(dayLabels).map(day => {
        const h = hours[day] || { enabled: day !== 'friday', open: '08:00', close: '22:00' };
        return `
            <div class="flex flex-wrap items-center gap-2 border rounded-lg p-2">
                <label class="flex items-center gap-2 w-24">
                    <input type="checkbox" data-day="${day}" class="bwh-enabled rounded text-emerald-600" ${h.enabled ? 'checked' : ''}>
                    <span class="text-xs">${dayLabels[day]}</span>
                </label>
                <input type="time" data-day="${day}" class="bwh-open px-2 py-1 border rounded text-xs" value="${h.open || '08:00'}">
                <span class="text-gray-400">—</span>
                <input type="time" data-day="${day}" class="bwh-close px-2 py-1 border rounded text-xs" value="${h.close || '22:00'}">
            </div>`;
    }).join('');
}

function collectBranchWorkingHours() {
    const hours = {};
    Object.keys(dayLabels).forEach(day => {
        hours[day] = {
            enabled: document.querySelector(`.bwh-enabled[data-day="${day}"]`)?.checked || false,
            open: document.querySelector(`.bwh-open[data-day="${day}"]`)?.value || '08:00',
            close: document.querySelector(`.bwh-close[data-day="${day}"]`)?.value || '22:00',
        };
    });
    return hours;
}

async function saveBranch(e) {
    e.preventDefault();
    clearFieldErrors();
    const id = document.getElementById('branchId').value;
    const body = {
        branch_name: document.getElementById('branch_name').value,
        governorate_id: document.getElementById('branch_governorate_id').value || null,
        district: document.getElementById('branch_district').value,
        address: document.getElementById('branch_address').value,
        phone: document.getElementById('branch_phone').value,
        is_primary: document.getElementById('branch_is_primary').checked,
        working_hours: collectBranchWorkingHours(),
        latitude: document.getElementById('branch_latitude').value || null,
        longitude: document.getElementById('branch_longitude').value || null,
    };
    const url = id ? `/pharmacy/api/branches/${id}` : '/pharmacy/api/branches';
    const data = await apiCall(url, { method: id ? 'PUT' : 'POST', body: JSON.stringify(body) });
    if (data?.success) {
        closeBranchModal();
        loadBranches();
    } else {
        handleApiError(data);
    }
}

async function deleteBranch(id) {
    if (!await confirmAction('حذف هذا الفرع؟')) return;
    const data = await apiCall(`/pharmacy/api/branches/${id}`, { method: 'DELETE' });
    if (data?.success) loadBranches();
    else if (data) alert(data.message || 'تعذر الحذف');
}

let branchMap = null, branchMarker = null;

function initBranchMap(lat, lng) {
    const latInput = document.getElementById('branch_latitude');
    const lngInput = document.getElementById('branch_longitude');
    const defLat = parseFloat(lat) || 33.3152;
    const defLng = parseFloat(lng) || 44.3661;
    if (!latInput.value) { latInput.value = defLat.toFixed(7); lngInput.value = defLng.toFixed(7); }

    if (branchMap) { branchMap.remove(); branchMap = null; }
    branchMap = L.map('branch-map').setView([defLat, defLng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(branchMap);
    branchMarker = L.marker([defLat, defLng], { draggable: true }).addTo(branchMap);

    function updateCoords(la, lo) {
        latInput.value = la.toFixed(7);
        lngInput.value = lo.toFixed(7);
    }
    branchMap.on('click', e => { branchMarker.setLatLng(e.latlng); updateCoords(e.latlng.lat, e.latlng.lng); });
    branchMarker.on('dragend', () => { const p = branchMarker.getLatLng(); updateCoords(p.lat, p.lng); });
    setTimeout(() => branchMap.invalidateSize(), 200);
}

function detectBranchLocation() {
    const btn = document.getElementById('detectBranchBtn');
    if (!navigator.geolocation) { alert('المتصفح لا يدعم تحديد الموقع'); return; }
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التحديد...';
    navigator.geolocation.getCurrentPosition(
        function(pos) {
            const lat = pos.coords.latitude, lng = pos.coords.longitude;
            document.getElementById('branch_latitude').value = lat.toFixed(7);
            document.getElementById('branch_longitude').value = lng.toFixed(7);
            if (branchMarker) branchMarker.setLatLng([lat, lng]);
            if (branchMap) branchMap.setView([lat, lng], 16);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-location-arrow"></i> تحديد موقعي الحالي';
        },
        function() {
            alert('تعذر تحديد الموقع.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-location-arrow"></i> تحديد موقعي الحالي';
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}
</script>
@endsection
