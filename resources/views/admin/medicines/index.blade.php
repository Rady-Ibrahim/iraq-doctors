@extends('admin.layout')

@section('title', 'كتالوج الأدوية')
@section('page-title', 'كتالوج الأدوية')
@section('page-description', 'عرض قاموس الأدوية المشترك — الإضافة من الصيدليات')

@section('content')
<div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg mb-6 text-sm">
    <i class="fas fa-info-circle ml-1"></i>
    الأدوية تُضاف من <strong>الصيدليات</strong>. هنا عرض وتقارير فقط.
</div>
<div class="mb-6 flex gap-4 border-b">
    <button onclick="showTab('categories')" id="tab-categories" class="px-4 py-3 border-b-2 border-blue-600 text-blue-600 font-semibold">التصنيفات</button>
    <button onclick="showTab('medicines')" id="tab-medicines" class="px-4 py-3 border-b-2 border-transparent text-gray-600 hover:text-gray-800">الأدوية</button>
</div>

<div id="panel-categories">
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6 hidden" id="categoryAddPanel">
        <h3 class="text-lg font-semibold mb-4">إضافة تصنيف</h3>
        <form id="categoryForm" class="grid grid-cols-1 md:grid-cols-4 gap-4" onsubmit="saveCategory(event)">
            <input type="hidden" id="categoryId">
            <div>
                <label class="block text-sm font-semibold mb-1">الاسم بالعربية</label>
                <input type="text" id="categoryNameAr" required class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">الاسم بالإنجليزية</label>
                <input type="text" id="categoryNameEn" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">الأيقونة</label>
                <input type="text" id="categoryIcon" placeholder="fas fa-pills" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg">حفظ</button>
                <button type="button" onclick="resetCategoryForm()" class="px-4 py-2 border rounded-lg text-gray-600">إلغاء</button>
            </div>
        </form>
    </div>
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-right text-sm font-semibold">الاسم</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">عدد الأدوية</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">الحالة</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">إجراءات</th>
                </tr>
            </thead>
            <tbody id="categoriesBody"><tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">جاري التحميل...</td></tr></tbody>
        </table>
    </div>
</div>

<div id="panel-medicines" class="hidden">
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6 hidden" id="medicineAddPanel">
        <h3 class="text-lg font-semibold mb-4">إضافة دواء</h3>
        <form id="medicineForm" class="grid grid-cols-1 md:grid-cols-3 gap-4" onsubmit="saveMedicine(event)">
            <input type="hidden" id="medicineId">
            <div>
                <label class="block text-sm font-semibold mb-1">التصنيف</label>
                <select id="medicineCategoryId" required class="w-full px-4 py-2 border rounded-lg"></select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">اسم الدواء (عربي)</label>
                <input type="text" id="medicineNameAr" required class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">الاسم العلمي</label>
                <input type="text" id="medicineGenericName" placeholder="Paracetamol" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">الباركود</label>
                <input type="text" id="medicineBarcode" placeholder="6281000000000" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">شكل الدواء</label>
                <input type="text" id="medicineDosageForm" placeholder="أقراص" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">التركيز</label>
                <input type="text" id="medicineStrength" placeholder="500 مجم" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">الشركة المصنعة</label>
                <input type="text" id="medicineManufacturer" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold mb-1">الوصف</label>
                <input type="text" id="medicineDescription" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div class="md:col-span-3 flex gap-2">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg">حفظ الدواء</button>
                <button type="button" onclick="resetMedicineForm()" class="px-6 py-2 border rounded-lg text-gray-600">إلغاء</button>
            </div>
        </form>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4 flex gap-4">
        <select id="filterCategory" onchange="loadMedicines()" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">كل التصنيفات</option>
        </select>
        <input type="text" id="searchMedicines" placeholder="بحث بالاسم أو الباركود..." oninput="loadMedicines()" class="flex-1 px-3 py-2 border rounded-lg text-sm">
    </div>
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-right text-sm font-semibold">الدواء</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">التصنيف</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">الباركود</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">الشكل الصيدلاني</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">الحالة</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">إجراءات</th>
                </tr>
            </thead>
            <tbody id="medicinesBody"><tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">جاري التحميل...</td></tr></tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
let categoriesCache = [];

window.addEventListener('load', async () => {
    await loadCategories();
    await loadMedicines();
});

function showTab(tab) {
    ['categories', 'medicines'].forEach(t => {
        document.getElementById('panel-' + t).classList.toggle('hidden', t !== tab);
        const btn = document.getElementById('tab-' + t);
        btn.className = t === tab
            ? 'px-4 py-3 border-b-2 border-blue-600 text-blue-600 font-semibold'
            : 'px-4 py-3 border-b-2 border-transparent text-gray-600 hover:text-gray-800';
    });
}

async function loadCategories() {
    const data = await apiCall('/admin/api/medicine-categories');
    const tbody = document.getElementById('categoriesBody');
    if (!data?.success) return;

    categoriesCache = data.data || [];
    fillCategorySelects();

    if (!categoriesCache.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">لا توجد تصنيفات</td></tr>';
        return;
    }

    tbody.innerHTML = categoriesCache.map(c => `
        <tr class="border-b hover:bg-gray-50">
            <td class="px-6 py-4 font-semibold">${c.name_ar}</td>
            <td class="px-6 py-4">${c.medicines_count || 0}</td>
            <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full ${c.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100'}">${c.is_active ? 'نشط' : 'معطّل'}</span></td>
            <td class="px-6 py-4 text-gray-400 text-sm">عرض فقط</td>
        </tr>
    `).join('');
}

function fillCategorySelects() {
    const opts = categoriesCache.map(c => `<option value="${c.id}">${c.name_ar}</option>`).join('');
    document.getElementById('medicineCategoryId').innerHTML = '<option value="">اختر التصنيف</option>' + opts;
    document.getElementById('filterCategory').innerHTML = '<option value="">كل التصنيفات</option>' + opts;
}

function resetCategoryForm() {
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryForm').reset();
}

function editCategory(c) {
    document.getElementById('categoryId').value = c.id;
    document.getElementById('categoryNameAr').value = c.name_ar;
    document.getElementById('categoryNameEn').value = c.name_en || '';
    document.getElementById('categoryIcon').value = c.icon || '';
}

async function saveCategory(e) {
    e.preventDefault();
    const id = document.getElementById('categoryId').value;
    const body = {
        name_ar: document.getElementById('categoryNameAr').value,
        name_en: document.getElementById('categoryNameEn').value || null,
        icon: document.getElementById('categoryIcon').value || null,
        is_active: true,
    };
    const url = id ? `/admin/api/medicine-categories/${id}` : '/admin/api/medicine-categories';
    const data = await apiCall(url, { method: id ? 'PUT' : 'POST', body: JSON.stringify(body) });
    if (data?.success) { resetCategoryForm(); loadCategories(); showSuccess(data.message); }
}

async function toggleCategory(id, isActive) {
    const cat = categoriesCache.find(c => c.id === id);
    if (!cat) return;
    const data = await apiCall(`/admin/api/medicine-categories/${id}`, {
        method: 'PUT',
        body: JSON.stringify({ name_ar: cat.name_ar, name_en: cat.name_en, icon: cat.icon, is_active: isActive }),
    });
    if (data?.success) loadCategories();
}

async function deleteCategory(id) {
    if (!await confirmAction('حذف هذا التصنيف؟')) return;
    const data = await apiCall(`/admin/api/medicine-categories/${id}`, { method: 'DELETE' });
    if (data?.success) loadCategories();
    else if (data) alert(data.message || 'تعذر الحذف');
}

async function loadMedicines() {
    const params = new URLSearchParams();
    const cat = document.getElementById('filterCategory')?.value;
    const search = document.getElementById('searchMedicines')?.value;
    if (cat) params.set('category_id', cat);
    if (search) params.set('search', search);

    const data = await apiCall(`/admin/api/medicines?${params}`);
    const tbody = document.getElementById('medicinesBody');
    if (!data?.success || !data.data?.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">لا توجد أدوية</td></tr>';
        return;
    }

    tbody.innerHTML = data.data.map(m => `
        <tr class="border-b hover:bg-gray-50">
            <td class="px-6 py-4">
                <p class="font-semibold">${m.name_ar}</p>
                ${m.generic_name ? `<p class="text-xs text-gray-500">${m.generic_name}</p>` : ''}
                ${m.strength ? `<p class="text-xs text-gray-400">${m.strength}</p>` : ''}
            </td>
            <td class="px-6 py-4">${m.category_name || '-'}</td>
            <td class="px-6 py-4">${m.barcode || '-'}</td>
            <td class="px-6 py-4">${m.dosage_form || '-'}</td>
            <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full ${m.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100'}">${m.is_active ? 'نشط' : 'معطّل'}</span></td>
            <td class="px-6 py-4 text-gray-400 text-sm">عرض فقط</td>
        </tr>
    `).join('');
}

function resetMedicineForm() {
    document.getElementById('medicineId').value = '';
    document.getElementById('medicineForm').reset();
}

function editMedicine(m) {
    document.getElementById('medicineId').value = m.id;
    document.getElementById('medicineCategoryId').value = m.medicine_category_id;
    document.getElementById('medicineNameAr').value = m.name_ar;
    document.getElementById('medicineGenericName').value = m.generic_name || '';
    document.getElementById('medicineBarcode').value = m.barcode || '';
    document.getElementById('medicineDosageForm').value = m.dosage_form || '';
    document.getElementById('medicineStrength').value = m.strength || '';
    document.getElementById('medicineManufacturer').value = m.manufacturer || '';
    document.getElementById('medicineDescription').value = m.description_ar || '';
    showTab('medicines');
}

async function saveMedicine(e) {
    e.preventDefault();
    const id = document.getElementById('medicineId').value;
    const body = {
        medicine_category_id: parseInt(document.getElementById('medicineCategoryId').value),
        name_ar: document.getElementById('medicineNameAr').value,
        generic_name: document.getElementById('medicineGenericName').value || null,
        barcode: document.getElementById('medicineBarcode').value || null,
        dosage_form: document.getElementById('medicineDosageForm').value || null,
        strength: document.getElementById('medicineStrength').value || null,
        manufacturer: document.getElementById('medicineManufacturer').value || null,
        description_ar: document.getElementById('medicineDescription').value || null,
        is_active: true,
    };
    const url = id ? `/admin/api/medicines/${id}` : '/admin/api/medicines';
    const data = await apiCall(url, { method: id ? 'PUT' : 'POST', body: JSON.stringify(body) });
    if (data?.success) { resetMedicineForm(); loadMedicines(); loadCategories(); showSuccess(data.message); }
}

async function toggleMedicine(id, isActive) {
    const res = await apiCall('/admin/api/medicines?');
    const medicine = res?.data?.find(m => m.id === id);
    if (!medicine) return;
    const data = await apiCall(`/admin/api/medicines/${id}`, {
        method: 'PUT',
        body: JSON.stringify({
            medicine_category_id: medicine.medicine_category_id,
            name_ar: medicine.name_ar,
            generic_name: medicine.generic_name,
            barcode: medicine.barcode,
            dosage_form: medicine.dosage_form,
            strength: medicine.strength,
            manufacturer: medicine.manufacturer,
            description_ar: medicine.description_ar,
            is_active: isActive,
        }),
    });
    if (data?.success) loadMedicines();
}

async function deleteMedicine(id) {
    if (!await confirmAction('حذف هذا الدواء؟')) return;
    const data = await apiCall(`/admin/api/medicines/${id}`, { method: 'DELETE' });
    if (data?.success) { loadMedicines(); loadCategories(); }
    else if (data) alert(data.message || 'تعذر الحذف');
}
</script>
@endsection
