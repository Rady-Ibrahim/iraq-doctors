@extends('admin.layout')

@section('title', 'إدارة التقييمات')
@section('page-title', 'تقييمات المرضى')
@section('page-description', 'مراجعة وموافقة التقييمات قبل عرضها للمرضى')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <p class="text-sm text-gray-600">بانتظار الموافقة</p>
        <p class="text-2xl font-bold text-yellow-600" id="pendingCount">0</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <p class="text-sm text-gray-600">معتمدة</p>
        <p class="text-2xl font-bold text-green-600" id="approvedCount">—</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <p class="text-sm text-gray-600">مرفوضة</p>
        <p class="text-2xl font-bold text-red-600" id="rejectedCount">—</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">البحث</label>
            <input type="text" id="searchInput" placeholder="مريض، طبيب، تعليق..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">الحالة</label>
            <select id="statusFilter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">جميع الحالات</option>
                <option value="pending">بانتظار الموافقة</option>
                <option value="approved">معتمدة</option>
                <option value="rejected">مرفوضة</option>
            </select>
        </div>
        <div class="md:col-span-2 flex items-end">
            <button onclick="applyFilters()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-search ml-2"></i>بحث
            </button>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">المريض</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الطبيب</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">التقييم</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">التعليق</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الحالة</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">التاريخ</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الإجراء</th>
                </tr>
            </thead>
            <tbody id="reviewsTableBody">
                <tr>
                    <td colspan="7" class="py-8 text-center text-gray-500">جاري التحميل...</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t flex items-center justify-between">
        <div class="text-sm text-gray-600" id="paginationInfo">عرض 0 من 0</div>
        <div class="flex gap-2" id="paginationButtons"></div>
    </div>
</div>

<div id="rejectModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">رفض التقييم</h3>
        <textarea id="rejectReason" rows="3" class="w-full border rounded-lg px-3 py-2" placeholder="سبب الرفض (اختياري)"></textarea>
        <input type="hidden" id="rejectReviewId">
        <div class="flex gap-3 mt-4 justify-end">
            <button onclick="closeRejectModal()" class="px-4 py-2 border rounded-lg">إلغاء</button>
            <button onclick="confirmReject()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">رفض</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let currentPage = 1;
let currentFilters = {};

window.addEventListener('load', async function() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('status')) {
        document.getElementById('statusFilter').value = params.get('status');
        currentFilters.status = params.get('status');
    }
    await loadReviews();
});

async function loadReviews(page = 1) {
    try {
        showLoading();
        const params = new URLSearchParams({ page, limit: 20, ...currentFilters });
        const data = await apiCall(`/admin/api/reviews?${params}`);

        if (data.success) {
            renderReviewsTable(data.data || []);
            renderPagination(data.meta);
            currentPage = page;
            updateStatusCounts();
        }
    } catch (error) {
        console.error(error);
        showError('حدث خطأ أثناء تحميل التقييمات');
    } finally {
        hideLoading();
    }
}

async function updateStatusCounts() {
    for (const status of ['pending', 'approved', 'rejected']) {
        const data = await apiCall(`/admin/api/reviews?status=${status}&limit=1`);
        const el = document.getElementById(status === 'pending' ? 'pendingCount' : status === 'approved' ? 'approvedCount' : 'rejectedCount');
        if (el && data?.meta) el.textContent = data.meta.total || 0;
    }
}

function renderReviewsTable(reviews) {
    const tbody = document.getElementById('reviewsTableBody');

    if (!reviews.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-gray-500">لا توجد تقييمات</td></tr>';
        return;
    }

    tbody.innerHTML = reviews.map((review) => `
        <tr class="border-b hover:bg-gray-50">
            <td class="px-6 py-4 text-sm">${review.patient_name || '—'}</td>
            <td class="px-6 py-4 text-sm">
                <div class="font-semibold">${review.doctor_name || '—'}</div>
                <div class="text-xs text-gray-500">${review.speciality || ''}</div>
            </td>
            <td class="px-6 py-4 text-sm text-amber-500">${'★'.repeat(review.rating)}${'☆'.repeat(5 - review.rating)}</td>
            <td class="px-6 py-4 text-sm max-w-xs truncate" title="${review.comment || ''}">${review.comment || '—'}</td>
            <td class="px-6 py-4">${statusBadge(review.status)}</td>
            <td class="px-6 py-4 text-sm text-gray-500">${review.created_at || ''}</td>
            <td class="px-6 py-4 text-sm">
                ${review.status === 'pending' ? `
                    <button onclick="approveReview(${review.id})" class="text-green-600 hover:text-green-700 ml-3">موافقة</button>
                    <button onclick="openRejectModal(${review.id})" class="text-red-600 hover:text-red-700">رفض</button>
                ` : '<span class="text-gray-400">—</span>'}
            </td>
        </tr>
    `).join('');
}

function statusBadge(status) {
    const map = {
        pending: '<span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">بانتظار الموافقة</span>',
        approved: '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">معتمد</span>',
        rejected: '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">مرفوض</span>',
    };
    return map[status] || status;
}

function applyFilters() {
    currentFilters = {};
    const search = document.getElementById('searchInput').value.trim();
    const status = document.getElementById('statusFilter').value;
    if (search) currentFilters.search = search;
    if (status) currentFilters.status = status;
    loadReviews(1);
}

async function approveReview(id) {
    const confirmed = await confirmAction('الموافقة على التقييم', 'سيُعرض التقييم للمرضى ويُحدَّث متوسط تقييم الطبيب.');
    if (!confirmed) return;

    const data = await apiPost(`/admin/api/reviews/${id}/approve`);
    if (data?.success) {
        showSuccess(data.message || 'تمت الموافقة');
        loadReviews(currentPage);
    } else {
        showError(data?.error?.message || 'فشلت العملية');
    }
}

function openRejectModal(id) {
    document.getElementById('rejectReviewId').value = id;
    document.getElementById('rejectReason').value = '';
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectModal').classList.add('flex');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectModal').classList.remove('flex');
}

async function confirmReject() {
    const id = document.getElementById('rejectReviewId').value;
    const reason = document.getElementById('rejectReason').value.trim();
    const data = await apiPost(`/admin/api/reviews/${id}/reject`, { reason });
    closeRejectModal();

    if (data?.success) {
        showSuccess(data.message || 'تم الرفض');
        loadReviews(currentPage);
    } else {
        showError(data?.error?.message || 'فشلت العملية');
    }
}

function renderPagination(meta) {
    if (!meta) return;
    document.getElementById('paginationInfo').textContent =
        `عرض ${meta.from || 0} - ${meta.to || 0} من ${meta.total || 0}`;

    const buttons = document.getElementById('paginationButtons');
    buttons.innerHTML = '';
    if (meta.current_page > 1) {
        buttons.innerHTML += `<button onclick="loadReviews(${meta.current_page - 1})" class="px-3 py-1 border rounded">السابق</button>`;
    }
    if (meta.current_page < meta.last_page) {
        buttons.innerHTML += `<button onclick="loadReviews(${meta.current_page + 1})" class="px-3 py-1 border rounded">التالي</button>`;
    }
}
</script>
@endsection
