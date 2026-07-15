@extends('admin.layout')

@section('title', 'تفاصيل المعمل')
@section('page-title', 'تفاصيل المعمل')

@section('content')
<div class="mb-6">
    <a href="/admin/dashboard/laboratories" class="text-blue-600 hover:text-blue-700 flex items-center gap-2">
        <i class="fas fa-arrow-right"></i><span>العودة إلى المعامل</span>
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6">
    <h2 class="text-2xl font-bold text-gray-800" id="labName">جاري التحميل...</h2>
    <p class="text-gray-600 mt-1" id="labOwner">-</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 text-sm">
        <p><span class="font-semibold">الهاتف:</span> <span id="labPhone">-</span></p>
        <p><span class="font-semibold">البريد:</span> <span id="labEmail">-</span></p>
        <p><span class="font-semibold">المحافظة:</span> <span id="labGovernorate">-</span></p>
        <p><span class="font-semibold">المنطقة:</span> <span id="labDistrict">-</span></p>
        <p class="md:col-span-2"><span class="font-semibold">العنوان:</span> <span id="labAddress">-</span></p>
    </div>

    <div class="border-t mt-6 pt-6">
        <h3 class="font-semibold text-gray-800 mb-3">المستندات</h3>
        <div id="labDocuments" class="flex flex-wrap gap-4 text-sm"></div>
        <p id="rejectReason" class="text-red-600 text-sm mt-3 hidden"></p>
    </div>

    <div class="border-t mt-6 pt-6 flex items-center gap-4">
        <span id="labStatusBadge"></span>
        <div id="actionButtons" class="flex gap-2"></div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const laboratoryId = {{ $laboratoryId }};

function docLink(url, label) {
    if (!url) return '';
    return `<a href="${url}" target="_blank" class="px-3 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">${label}</a>`;
}

async function loadLaboratory() {
    const data = await apiCall(`/admin/api/laboratories/${laboratoryId}`);
    if (!data?.success) return;
    const lab = data.data;

    document.getElementById('labName').textContent = lab.name || '-';
    document.getElementById('labOwner').textContent = lab.owner_name || '-';
    document.getElementById('labPhone').textContent = lab.phone || '-';
    document.getElementById('labEmail').textContent = lab.email || '-';
    document.getElementById('labGovernorate').textContent = lab.governorate || '-';
    document.getElementById('labDistrict').textContent = lab.district || '-';
    document.getElementById('labAddress').textContent = lab.address || '-';

    document.getElementById('labDocuments').innerHTML = [
        docLink(lab.logo, 'الشعار'),
        docLink(lab.commercial_register_document, 'السجل التجاري'),
        docLink(lab.license_document, 'الترخيص'),
        docLink(lab.owner_id_document, 'هوية المالك'),
        docLink(lab.accreditation_document, 'شهادة الاعتماد'),
    ].filter(Boolean).join('') || '<span class="text-gray-500">لا توجد مستندات</span>';

    if (lab.reject_reason) {
        const el = document.getElementById('rejectReason');
        el.textContent = 'سبب الرفض: ' + lab.reject_reason;
        el.classList.remove('hidden');
    }

    const statusLabels = { pending: 'معلق', approved: 'موافق', rejected: 'مرفوض', suspended: 'معلّق' };
    document.getElementById('labStatusBadge').innerHTML =
        `<span class="px-3 py-1 rounded-full bg-gray-100 text-sm font-semibold">${statusLabels[lab.status] || lab.status}</span>`;

    const actions = document.getElementById('actionButtons');
    if (lab.status === 'pending' || lab.status === 'rejected') {
        actions.innerHTML = `
            <button onclick="approveLab()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                <i class="fas fa-check ml-1"></i> موافقة
            </button>
            <button onclick="rejectLab()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                <i class="fas fa-times ml-1"></i> رفض
            </button>
        `;
    } else if (lab.status === 'approved') {
        actions.innerHTML = `
            <button onclick="suspendLab()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                <i class="fas fa-pause ml-1"></i> تعليق
            </button>
        `;
    } else if (lab.status === 'suspended') {
        actions.innerHTML = `
            <button onclick="reactivateLab()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                <i class="fas fa-play ml-1"></i> إعادة التفعيل
            </button>
        `;
    } else {
        actions.innerHTML = '';
    }
}

async function approveLab() {
    const data = await apiCall(`/admin/api/laboratories/${laboratoryId}/approve`, { method: 'POST', body: '{}' });
    if (data?.success) { showSuccess('تمت الموافقة على المعمل'); loadLaboratory(); }
}

async function reactivateLab() {
    if (!confirm('إعادة تفعيل هذا المعمل؟ سيتمكن من الدخول واستخدام الداشبورد مرة أخرى.')) return;
    const data = await apiCall(`/admin/api/laboratories/${laboratoryId}/approve`, { method: 'POST', body: '{}' });
    if (data?.success) { showSuccess('تم إعادة تفعيل المعمل'); loadLaboratory(); }
}

async function rejectLab() {
    const reason = prompt('سبب الرفض (اختياري):');
    if (reason === null) return;
    const data = await apiCall(`/admin/api/laboratories/${laboratoryId}/reject`, {
        method: 'POST',
        body: JSON.stringify({ reject_reason: reason }),
    });
    if (data?.success) { showSuccess('تم الرفض'); loadLaboratory(); }
}

async function suspendLab() {
    if (!confirm('تعليق هذا المعمل؟')) return;
    const data = await apiCall(`/admin/api/laboratories/${laboratoryId}/suspend`, { method: 'POST', body: '{}' });
    if (data?.success) { showSuccess('تم التعليق'); loadLaboratory(); }
}

document.addEventListener('DOMContentLoaded', loadLaboratory);
</script>
@endsection
