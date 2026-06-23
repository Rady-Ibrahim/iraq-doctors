@extends('admin.layout')

@section('title', 'إدارة التخصصات')
@section('page-title', 'التخصصات')
@section('page-description', 'إدارة تخصصات الأطباء')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">إضافة تخصص جديد</h3>
    <form id="specialityForm" class="grid grid-cols-1 md:grid-cols-4 gap-4" onsubmit="saveSpeciality(event)">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">الاسم بالعربية</label>
            <input type="text" id="nameAr" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">الاسم بالإنجليزية</label>
            <input type="text" id="nameEn" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">الأيقونة</label>
            <input type="text" id="icon" placeholder="fas fa-heart" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-plus ml-2"></i>إضافة
            </button>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الاسم (عربي)</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الاسم (إنجليزي)</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الحالة</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الإجراء</th>
            </tr>
        </thead>
        <tbody id="specialitiesTableBody">
            <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">جاري التحميل...</td></tr>
        </tbody>
    </table>
</div>
@endsection

@section('scripts')
<script>
window.addEventListener('load', loadSpecialities);

async function loadSpecialities() {
    const data = await apiCall('/admin/api/specialities');
    const tbody = document.getElementById('specialitiesTableBody');

    if (!data?.success || data.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">لا توجد تخصصات</td></tr>';
        return;
    }

    tbody.innerHTML = data.data.map(item => `
        <tr class="border-b hover:bg-gray-50">
            <td class="px-6 py-4 font-semibold">${item.name_ar}</td>
            <td class="px-6 py-4">${item.name_en}</td>
            <td class="px-6 py-4">
                <span class="px-3 py-1 rounded-full text-xs ${item.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                    ${item.is_active ? 'نشط' : 'غير نشط'}
                </span>
            </td>
            <td class="px-6 py-4">
                <button onclick="toggleSpeciality(${item.id}, ${item.is_active ? 'false' : 'true'})" class="px-3 py-1 bg-blue-100 text-blue-600 rounded text-sm ml-2">
                    ${item.is_active ? 'تعطيل' : 'تفعيل'}
                </button>
                <button onclick="deleteSpeciality(${item.id})" class="px-3 py-1 bg-red-100 text-red-600 rounded text-sm">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

async function saveSpeciality(event) {
    event.preventDefault();
    const data = await apiCall('/admin/api/specialities', {
        method: 'POST',
        body: JSON.stringify({
            name_ar: document.getElementById('nameAr').value,
            name_en: document.getElementById('nameEn').value,
            icon: document.getElementById('icon').value || null,
            is_active: true,
        }),
    });

    if (data?.success) {
        showSuccess('تم إضافة التخصص');
        document.getElementById('specialityForm').reset();
        loadSpecialities();
    }
}

async function toggleSpeciality(id, isActive) {
    const data = await apiCall(`/admin/api/specialities/${id}`, {
        method: 'PUT',
        body: JSON.stringify({ is_active: isActive }),
    });
    if (data?.success) loadSpecialities();
}

async function deleteSpeciality(id) {
    if (!await confirmAction('هل أنت متأكد من حذف هذا التخصص؟')) return;
    const data = await apiCall(`/admin/api/specialities/${id}`, { method: 'DELETE' });
    if (data?.success) {
        showSuccess('تم الحذف');
        loadSpecialities();
    }
}
</script>
@endsection
