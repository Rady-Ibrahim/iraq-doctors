@extends('admin.layout')

@section('title', 'تفاصيل الصيدلية')
@section('page-title', 'تفاصيل الصيدلية')

@section('content')
<div class="mb-6">
    <a href="/admin/dashboard/pharmacies" class="text-blue-600 hover:text-blue-700 flex items-center gap-2">
        <i class="fas fa-arrow-right"></i><span>العودة إلى الصيدليات</span>
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6">
    <h2 class="text-2xl font-bold text-gray-800" id="pharmacyName">جاري التحميل...</h2>
    <p class="text-gray-600 mt-1" id="pharmacyOwner">-</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 text-sm">
        <p><span class="font-semibold">الهاتف:</span> <span id="pharmacyPhone">-</span></p>
        <p><span class="font-semibold">البريد:</span> <span id="pharmacyEmail">-</span></p>
        <p><span class="font-semibold">المحافظة:</span> <span id="pharmacyGovernorate">-</span></p>
        <p><span class="font-semibold">المنطقة:</span> <span id="pharmacyDistrict">-</span></p>
        <p class="md:col-span-2"><span class="font-semibold">العنوان:</span> <span id="pharmacyAddress">-</span></p>
    </div>

    <div class="border-t mt-6 pt-6">
        <h3 class="font-semibold text-gray-800 mb-3">المستندات</h3>
        <div id="pharmacyDocuments" class="flex flex-wrap gap-4 text-sm"></div>
        <p id="rejectReason" class="text-red-600 text-sm mt-3 hidden"></p>
    </div>

    <div class="border-t mt-6 pt-6 flex items-center gap-4">
        <span id="pharmacyStatusBadge"></span>
        <div id="actionButtons" class="flex gap-2"></div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const pharmacyId = {{ $pharmacyId }};

function docLink(url, label) {
    if (!url) return '';
    return `<a href="${url}" target="_blank" class="px-3 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">${label}</a>`;
}

async function loadPharmacy() {
    const data = await apiCall(`/admin/api/pharmacies/${pharmacyId}`);
    if (!data?.success) return;
    const pharmacy = data.data;

    document.getElementById('pharmacyName').textContent = pharmacy.name || '-';
    document.getElementById('pharmacyOwner').textContent = pharmacy.owner_name || '-';
    document.getElementById('pharmacyPhone').textContent = pharmacy.phone || '-';
    document.getElementById('pharmacyEmail').textContent = pharmacy.email || '-';
    document.getElementById('pharmacyGovernorate').textContent = pharmacy.governorate || '-';
    document.getElementById('pharmacyDistrict').textContent = pharmacy.district || '-';
    document.getElementById('pharmacyAddress').textContent = pharmacy.address || '-';

    document.getElementById('pharmacyDocuments').innerHTML = [
        docLink(pharmacy.logo, 'الشعار'),
        docLink(pharmacy.commercial_register_document, 'السجل التجاري'),
        docLink(pharmacy.license_document, 'الترخيص'),
        docLink(pharmacy.owner_id_document, 'هوية المالك'),
    ].filter(Boolean).join('') || '<span class="text-gray-500">لا توجد مستندات</span>';

    if (pharmacy.reject_reason) {
        const el = document.getElementById('rejectReason');
        el.textContent = 'سبب الرفض: ' + pharmacy.reject_reason;
        el.classList.remove('hidden');
    }

    const statusLabels = { pending: 'معلق', approved: 'موافق', rejected: 'مرفوض', suspended: 'معلّق' };
    document.getElementById('pharmacyStatusBadge').innerHTML =
        `<span class="px-3 py-1 rounded-full bg-gray-100 text-sm font-semibold">${statusLabels[pharmacy.status] || pharmacy.status}</span>`;

    const actions = document.getElementById('actionButtons');
    if (pharmacy.status === 'pending' || pharmacy.status === 'rejected') {
        actions.innerHTML = `
            <button onclick="approvePharmacy()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                <i class="fas fa-check ml-1"></i> موافقة
            </button>
            <button onclick="rejectPharmacy()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                <i class="fas fa-times ml-1"></i> رفض
            </button>
        `;
    } else if (pharmacy.status === 'approved') {
        actions.innerHTML = `
            <button onclick="suspendPharmacy()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                <i class="fas fa-pause ml-1"></i> تعليق
            </button>
        `;
    } else if (pharmacy.status === 'suspended') {
        actions.innerHTML = `
            <button onclick="reactivatePharmacy()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                <i class="fas fa-play ml-1"></i> إعادة التفعيل
            </button>
        `;
    } else {
        actions.innerHTML = '';
    }
}

async function approvePharmacy() {
    const data = await apiCall(`/admin/api/pharmacies/${pharmacyId}/approve`, { method: 'POST', body: '{}' });
    if (data?.success) { showSuccess('تمت الموافقة على الصيدلية'); loadPharmacy(); }
}

async function reactivatePharmacy() {
    if (!confirm('إعادة تفعيل هذه الصيدلية؟ سيتمكن من الدخول واستخدام الداشبورد مرة أخرى.')) return;
    const data = await apiCall(`/admin/api/pharmacies/${pharmacyId}/approve`, { method: 'POST', body: '{}' });
    if (data?.success) { showSuccess('تم إعادة تفعيل الصيدلية'); loadPharmacy(); }
}

async function rejectPharmacy() {
    const reason = prompt('سبب الرفض (اختياري):');
    if (reason === null) return;
    const data = await apiCall(`/admin/api/pharmacies/${pharmacyId}/reject`, {
        method: 'POST',
        body: JSON.stringify({ reject_reason: reason }),
    });
    if (data?.success) { showSuccess('تم الرفض'); loadPharmacy(); }
}

async function suspendPharmacy() {
    if (!confirm('تعليق هذه الصيدلية؟')) return;
    const data = await apiCall(`/admin/api/pharmacies/${pharmacyId}/suspend`, { method: 'POST', body: '{}' });
    if (data?.success) { showSuccess('تم التعليق'); loadPharmacy(); }
}

document.addEventListener('DOMContentLoaded', loadPharmacy);
</script>
@endsection
