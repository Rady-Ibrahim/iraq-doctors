@extends('doctor.layout')

@section('title', 'مرضاي')
@section('page-title', 'المرضى')
@section('page-description', 'إدارة قائمة مرضاك والسجلات الطبية')

@section('content')
<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">البحث</label>
            <input type="text" id="searchInput" placeholder="ابحث عن مريض..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">الترتيب</label>
            <select id="sortFilter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                <option value="latest">الأحدث أولاً</option>
                <option value="oldest">الأقدم أولاً</option>
                <option value="name">حسب الاسم</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">&nbsp;</label>
            <button onclick="applyFilters()" class="w-full px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">
                <i class="fas fa-search ml-2"></i>بحث
            </button>
        </div>
    </div>
</div>

<!-- Patients Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="patientsGrid">
    <div class="col-span-full text-center py-12">
        <i class="fas fa-spinner fa-spin text-3xl text-teal-600 mb-4"></i>
        <p class="text-gray-500">جاري التحميل...</p>
    </div>
</div>

<!-- Pagination -->
<div class="mt-8 flex items-center justify-between">
    <div class="text-sm text-gray-600" id="paginationInfo">
        عرض 0 من 0
    </div>
    <div class="flex gap-2" id="paginationButtons">
        <!-- Pagination buttons will be added here -->
    </div>
</div>

@endsection

@section('scripts')
<script>
let currentPage = 1;
let currentFilters = {};

window.addEventListener('load', async function() {
    await loadPatients();
});

async function loadPatients(page = 1) {
    try {
        showLoading();
        
        const params = new URLSearchParams({
            page: page,
            limit: 20,
            ...currentFilters
        });

        const data = await apiCall(`/doctor/api/patients?${params}`);
        
        if (data.success) {
            renderPatientsGrid(data.data);
            renderPagination(data.meta);
            currentPage = page;
        } else {
            alert(data.error?.message || 'فشل تحميل المرضى');
        }
    } catch (error) {
        console.error('Error loading patients:', error);
        alert('حدث خطأ أثناء تحميل المرضى');
    } finally {
        hideLoading();
    }
}

function renderPatientsGrid(patients) {
    const patientsGrid = document.getElementById('patientsGrid');
    
    if (patients.length === 0) {
        patientsGrid.innerHTML = `
            <div class="col-span-full text-center py-12">
                <i class="fas fa-inbox text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-500">لا يوجد مرضى</p>
            </div>
        `;
        return;
    }

    patientsGrid.innerHTML = patients.map(patient => `
        <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-teal-600 text-2xl"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-800">${patient.name || 'غير محدد'}</h3>
                    <p class="text-sm text-gray-500">${patient.phone || '-'}</p>
                </div>
            </div>

            <div class="space-y-2 mb-4 pb-4 border-b">
                <p class="text-sm text-gray-600">
                    <span class="font-semibold">البريد:</span>
                    <span>${patient.email || '-'}</span>
                </p>
                <p class="text-sm text-gray-600">
                    <span class="font-semibold">المواعيد:</span>
                    <span>${patient.total_appointments || 0}</span>
                </p>
                <p class="text-sm text-gray-600">
                    <span class="font-semibold">آخر زيارة:</span>
                    <span>${patient.last_appointment_date ? formatDate(patient.last_appointment_date) : 'لم يوجد'}</span>
                </p>
            </div>

            <div class="flex gap-2">
                <a href="/doctor/dashboard/patients/${patient.id}" class="flex-1 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition text-center text-sm">
                    <i class="fas fa-eye ml-2"></i>عرض
                </a>
                <a href="/doctor/dashboard/patients/${patient.id}/records" class="flex-1 px-4 py-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition text-center text-sm">
                    <i class="fas fa-file-medical ml-2"></i>السجل
                </a>
            </div>
        </div>
    `).join('');
}

function renderPagination(meta) {
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationButtons = document.getElementById('paginationButtons');
    
    paginationInfo.textContent = `عرض ${(meta.page - 1) * meta.limit + 1} إلى ${Math.min(meta.page * meta.limit, meta.total)} من ${meta.total}`;
    
    let buttons = '';
    
    if (meta.page > 1) {
        buttons += `<button onclick="loadPatients(${meta.page - 1})" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">السابق</button>`;
    }
    
    for (let i = 1; i <= meta.last_page; i++) {
        if (i === meta.page) {
            buttons += `<button class="px-3 py-1 bg-teal-600 text-white rounded">${i}</button>`;
        } else if (i <= 3 || i > meta.last_page - 2 || Math.abs(i - meta.page) <= 1) {
            buttons += `<button onclick="loadPatients(${i})" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">${i}</button>`;
        }
    }
    
    if (meta.page < meta.last_page) {
        buttons += `<button onclick="loadPatients(${meta.page + 1})" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">التالي</button>`;
    }
    
    paginationButtons.innerHTML = buttons;
}

function applyFilters() {
    currentFilters = {
        search: document.getElementById('searchInput').value,
        sort_by: document.getElementById('sortFilter').value,
    };
    
    Object.keys(currentFilters).forEach(key => {
        if (!currentFilters[key]) delete currentFilters[key];
    });
    
    loadPatients(1);
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-IQ');
}
</script>
@endsection
