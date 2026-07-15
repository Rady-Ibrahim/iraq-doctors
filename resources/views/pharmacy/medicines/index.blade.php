@extends('pharmacy.layout')

@section('title', 'مخزون الأدوية')
@section('page-title', 'مخزون الأدوية')
@section('page-description', 'إدارة تصنيفات وأدوية صيدليتك — الأسماء تُشارك بين الصيدليات بدون أسعار')

@section('content')
<div class="mb-6 flex gap-1 border-b border-gray-200">
    <button type="button" onclick="showTab('medicines')" id="tab-medicines"
        class="px-5 py-3 text-sm font-semibold border-b-2 border-emerald-600 text-emerald-700 -mb-px">
        <i class="fas fa-pills ml-1"></i> الأدوية
    </button>
    <button type="button" onclick="showTab('categories')" id="tab-categories"
        class="px-5 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-800 -mb-px">
        <i class="fas fa-folder ml-1"></i> التصنيفات
    </button>
</div>

{{-- Medicines panel --}}
<div id="panel-medicines">
    <div class="flex flex-wrap gap-4 justify-between items-center mb-6">
        <div class="flex gap-3 flex-1">
            <select id="filterCategory" onchange="loadItems()" class="px-3 py-2 border rounded-lg text-sm bg-white min-w-[160px]">
                <option value="">كل التصنيفات</option>
            </select>
            <input type="text" id="searchInput" placeholder="بحث في أدويتك..." oninput="loadItems()"
                class="flex-1 min-w-[200px] px-3 py-2 border rounded-lg text-sm">
        </div>
        <button type="button" onclick="openAddModal()" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
            <i class="fas fa-plus ml-1"></i> إضافة دواء
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الدواء</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">التصنيف</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">السعر</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">المخزون</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الصلاحية</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الحالة</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">إجراءات</th>
                </tr>
            </thead>
            <tbody id="itemsBody">
                <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">جاري التحميل...</td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- Categories panel --}}
<div id="panel-categories" class="hidden">
    <div class="flex justify-between items-center mb-6">
        <p class="text-sm text-gray-600">التصنيفات مشتركة بين كل الصيدليات — أضف تصنيفاً قبل إدخال دواء جديد.</p>
        <button type="button" onclick="openCategoryModal()" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
            <i class="fas fa-plus ml-1"></i> تصنيف جديد
        </button>
    </div>
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">التصنيف</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الاسم بالإنجليزية</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">عدد الأدوية</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الترتيب</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">إجراءات</th>
                </tr>
            </thead>
            <tbody id="categoriesBody">
                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">جاري التحميل...</td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- Medicine modal --}}
<div id="itemModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex justify-between items-center">
            <h3 class="text-lg font-bold" id="modalTitle">إضافة دواء</h3>
            <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="itemForm" onsubmit="saveItem(event)" class="p-6 space-y-4">
            <input type="hidden" id="itemId">
            <input type="hidden" id="medicine_id">
            <div id="medicineFields">
                <div class="flex items-end gap-2 mb-3">
                    <div class="flex-1">
                        <label class="block text-sm font-semibold mb-1">التصنيف *</label>
                        <select id="medicine_category_id" required class="w-full px-4 py-2 border rounded-lg"></select>
                    </div>
                    <button type="button" onclick="openCategoryModal(true)" title="تصنيف جديد"
                        class="px-3 py-2 border border-emerald-300 text-emerald-700 rounded-lg hover:bg-emerald-50 text-sm whitespace-nowrap">
                        <i class="fas fa-plus"></i> جديد
                    </button>
                </div>
                <div id="noCategoriesHint" class="hidden mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                    لا توجد تصنيفات بعد.
                    <button type="button" onclick="openCategoryModal(true)" class="font-semibold text-emerald-700 underline mr-1">أنشئ تصنيفاً أولاً</button>
                </div>
                <label class="block text-sm font-semibold mb-1">اسم الدواء *</label>
                <input type="text" id="name_ar" required autocomplete="off"
                    oninput="debouncedSuggest()" onfocus="debouncedSuggest()"
                    class="w-full px-4 py-2 border rounded-lg" placeholder="اكتب اسم الدواء أو اختر من الاقتراحات">
                <div id="suggestions" class="hidden mt-1 border rounded-lg bg-white shadow-lg max-h-40 overflow-y-auto"></div>
                <p class="text-xs text-gray-500 mt-1">إذا كان الدواء موجوداً في النظام سيظهر اسمه فقط — أنت تحدد السعر والمخزون</p>
                <div class="grid grid-cols-2 gap-3 mt-3">
                    <div>
                        <label class="block text-sm font-semibold mb-1">الاسم العلمي</label>
                        <input type="text" id="generic_name" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">الباركود</label>
                        <input type="text" id="barcode" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">شكل الدواء</label>
                        <input type="text" id="dosage_form" placeholder="أقراص" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">التركيز</label>
                        <input type="text" id="strength" placeholder="500 مجم" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">السعر (د.ع) *</label>
                <input type="number" id="price" required min="0" step="1" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">الكمية في المخزون *</label>
                <input type="number" id="stock_quantity" required min="0" max="999999" value="0" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">تاريخ انتهاء الصلاحية (اختياري)</label>
                <input type="date" id="expiry_date" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">ملاحظات (اختياري)</label>
                <textarea id="notes" rows="2" class="w-full px-4 py-2 border rounded-lg"></textarea>
            </div>
            <label class="flex items-center gap-2" id="availableRow">
                <input type="checkbox" id="is_available" checked class="rounded text-emerald-600">
                <span class="text-sm">متاح للطلب</span>
            </label>
            <button type="submit" class="w-full py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">حفظ</button>
        </form>
    </div>
</div>

{{-- Category modal --}}
<div id="categoryModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[60] p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="p-6 border-b flex justify-between items-center">
            <h3 class="text-lg font-bold" id="categoryModalTitle">تصنيف جديد</h3>
            <button type="button" onclick="closeCategoryModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="categoryForm" onsubmit="saveCategory(event)" class="p-6 space-y-4">
            <input type="hidden" id="categoryId">
            <div>
                <label class="block text-sm font-semibold mb-1">الاسم بالعربية *</label>
                <input type="text" id="categoryNameAr" required maxlength="255"
                    class="w-full px-4 py-2 border rounded-lg" placeholder="مثال: مسكنات">
                <span class="text-red-500 text-sm" data-error-for="name_ar"></span>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">الاسم بالإنجليزية (اختياري)</label>
                <input type="text" id="categoryNameEn" maxlength="255" class="w-full px-4 py-2 border rounded-lg" placeholder="Analgesics">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold mb-1">أيقونة (اختياري)</label>
                    <input type="text" id="categoryIcon" maxlength="255" placeholder="fas fa-capsules"
                        class="w-full px-4 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">الترتيب</label>
                    <input type="number" id="categorySortOrder" min="0" max="9999" value="0"
                        class="w-full px-4 py-2 border rounded-lg">
                </div>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="flex-1 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">حفظ التصنيف</button>
                <button type="button" onclick="closeCategoryModal()" class="px-4 py-2 border rounded-lg text-gray-600">إلغاء</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
let suggestTimer = null;
let categoriesCache = [];
let categoryModalFromMedicine = false;

window.addEventListener('load', async () => {
    await loadCategories();
    await loadItems();
});

function showTab(tab) {
    const isMedicines = tab === 'medicines';
    document.getElementById('panel-medicines').classList.toggle('hidden', !isMedicines);
    document.getElementById('panel-categories').classList.toggle('hidden', isMedicines);
    document.getElementById('tab-medicines').className = isMedicines
        ? 'px-5 py-3 text-sm font-semibold border-b-2 border-emerald-600 text-emerald-700 -mb-px'
        : 'px-5 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-800 -mb-px';
    document.getElementById('tab-categories').className = !isMedicines
        ? 'px-5 py-3 text-sm font-semibold border-b-2 border-emerald-600 text-emerald-700 -mb-px'
        : 'px-5 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-800 -mb-px';
    if (!isMedicines) renderCategoriesTable();
}

function debouncedSuggest() {
    clearTimeout(suggestTimer);
    suggestTimer = setTimeout(loadSuggestions, 300);
}

async function loadCategories() {
    const data = await apiCall('/pharmacy/api/medicines/categories');
    if (!data?.success) return;
    categoriesCache = data.data || [];
    renderCategorySelects();
    renderCategoriesTable();
}

function renderCategorySelects() {
    const active = categoriesCache.filter(c => c.is_active !== false);
    const opts = active.map(c => `<option value="${c.id}">${c.name_ar}</option>`).join('');
    const filterOpts = '<option value="">كل التصنيفات</option>' + opts;
    const selectOpts = '<option value="">اختر التصنيف</option>' + opts;

    document.getElementById('filterCategory').innerHTML = filterOpts;
    document.getElementById('medicine_category_id').innerHTML = selectOpts;

    const hint = document.getElementById('noCategoriesHint');
    const catSelect = document.getElementById('medicine_category_id');
    if (active.length === 0) {
        hint.classList.remove('hidden');
        catSelect.required = false;
        catSelect.disabled = true;
    } else {
        hint.classList.add('hidden');
        catSelect.required = true;
        catSelect.disabled = false;
    }
}

function renderCategoriesTable() {
    const tbody = document.getElementById('categoriesBody');
    if (!categoriesCache.length) {
        tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-10 text-center">
            <p class="text-gray-500 mb-3">لا توجد تصنيفات بعد</p>
            <button type="button" onclick="openCategoryModal()" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm">إنشاء أول تصنيف</button>
        </td></tr>`;
        return;
    }

    tbody.innerHTML = categoriesCache.map(c => `
        <tr class="border-b hover:bg-gray-50">
            <td class="px-6 py-4">
                <span class="font-semibold">${c.name_ar}</span>
                ${c.icon ? `<i class="${c.icon} text-emerald-600 mr-2 text-sm"></i>` : ''}
            </td>
            <td class="px-6 py-4 text-sm text-gray-600">${c.name_en || '—'}</td>
            <td class="px-6 py-4 text-sm">${c.medicines_count ?? 0}</td>
            <td class="px-6 py-4 text-sm text-gray-500">${c.sort_order ?? 0}</td>
            <td class="px-6 py-4">
                <button type="button" onclick='editCategory(${JSON.stringify(c)})' class="text-emerald-600 text-sm">تعديل</button>
            </td>
        </tr>
    `).join('');
}

function openCategoryModal(fromMedicine = false) {
    categoryModalFromMedicine = fromMedicine;
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryModalTitle').textContent = 'تصنيف جديد';
    document.getElementById('categoryNameAr').value = '';
    document.getElementById('categoryNameEn').value = '';
    document.getElementById('categoryIcon').value = '';
    document.getElementById('categorySortOrder').value = '0';
    clearFieldErrors();
    document.getElementById('categoryModal').classList.remove('hidden');
    document.getElementById('categoryModal').classList.add('flex');
    setTimeout(() => document.getElementById('categoryNameAr').focus(), 100);
}

function editCategory(category) {
    categoryModalFromMedicine = false;
    document.getElementById('categoryId').value = category.id;
    document.getElementById('categoryModalTitle').textContent = 'تعديل التصنيف';
    document.getElementById('categoryNameAr').value = category.name_ar;
    document.getElementById('categoryNameEn').value = category.name_en || '';
    document.getElementById('categoryIcon').value = category.icon || '';
    document.getElementById('categorySortOrder').value = category.sort_order ?? 0;
    clearFieldErrors();
    document.getElementById('categoryModal').classList.remove('hidden');
    document.getElementById('categoryModal').classList.add('flex');
}

function closeCategoryModal() {
    document.getElementById('categoryModal').classList.add('hidden');
    document.getElementById('categoryModal').classList.remove('flex');
}

async function saveCategory(e) {
    e.preventDefault();
    clearFieldErrors();
    const id = document.getElementById('categoryId').value;
    const body = {
        name_ar: document.getElementById('categoryNameAr').value.trim(),
        name_en: document.getElementById('categoryNameEn').value.trim() || null,
        icon: document.getElementById('categoryIcon').value.trim() || null,
        sort_order: parseInt(document.getElementById('categorySortOrder').value) || 0,
    };

    const url = id ? `/pharmacy/api/medicines/categories/${id}` : '/pharmacy/api/medicines/categories';
    const data = await apiCall(url, { method: id ? 'PUT' : 'POST', body: JSON.stringify(body) });

    if (data?.success) {
        closeCategoryModal();
        await loadCategories();
        if (data.data?.id) {
            document.getElementById('medicine_category_id').value = data.data.id;
        }
        showSuccess(data.message || 'تم حفظ التصنيف');
        if (categoryModalFromMedicine && !document.getElementById('itemModal').classList.contains('hidden')) {
            document.getElementById('name_ar').focus();
        }
    } else {
        handleApiError(data);
    }
}

async function loadSuggestions() {
    const q = document.getElementById('name_ar').value.trim();
    const cat = document.getElementById('medicine_category_id').value;
    const box = document.getElementById('suggestions');
    if (q.length < 2) { box.classList.add('hidden'); return; }

    const params = new URLSearchParams({ q });
    if (cat) params.set('category_id', cat);
    const data = await apiCall(`/pharmacy/api/medicines/suggest?${params}`);
    if (!data?.success || !data.data?.length) { box.classList.add('hidden'); return; }

    box.innerHTML = data.data.map(m => `
        <button type="button" onclick='pickSuggestion(${JSON.stringify(m)})'
            class="w-full text-right px-3 py-2 hover:bg-emerald-50 text-sm border-b last:border-0 ${m.already_in_pharmacy ? 'opacity-50' : ''}">
            <span class="font-semibold">${m.name_ar}</span>
            ${m.generic_name ? `<span class="text-gray-500"> — ${m.generic_name}</span>` : ''}
            ${m.already_in_pharmacy ? '<span class="text-xs text-amber-600 mr-2">(مُضاف مسبقاً)</span>' : ''}
        </button>
    `).join('');
    box.classList.remove('hidden');
}

function pickSuggestion(medicine) {
    if (medicine.already_in_pharmacy) {
        showError('هذا الدواء مُضاف بالفعل لصيدليتك');
        return;
    }
    document.getElementById('medicine_id').value = medicine.id;
    document.getElementById('name_ar').value = medicine.name_ar;
    document.getElementById('generic_name').value = medicine.generic_name || '';
    document.getElementById('barcode').value = medicine.barcode || '';
    document.getElementById('dosage_form').value = medicine.dosage_form || '';
    document.getElementById('strength').value = medicine.strength || '';
    if (medicine.medicine_category_id) {
        document.getElementById('medicine_category_id').value = medicine.medicine_category_id;
    }
    document.getElementById('suggestions').classList.add('hidden');
}

async function loadItems() {
    const params = new URLSearchParams();
    const cat = document.getElementById('filterCategory').value;
    const search = document.getElementById('searchInput').value;
    if (cat) params.set('category_id', cat);
    if (search) params.set('search', search);

    const data = await apiCall(`/pharmacy/api/medicines?${params}`);
    const tbody = document.getElementById('itemsBody');
    if (!data?.success || !data.data?.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">لم تُضف أدوية بعد. اضغط «إضافة دواء».</td></tr>';
        return;
    }

    tbody.innerHTML = data.data.map(item => `
        <tr class="border-b hover:bg-gray-50">
            <td class="px-6 py-4">
                <p class="font-semibold">${item.name_ar}</p>
                ${item.generic_name ? `<p class="text-xs text-gray-500">${item.generic_name}</p>` : ''}
            </td>
            <td class="px-6 py-4 text-sm">${item.category_name || '-'}</td>
            <td class="px-6 py-4 font-semibold text-emerald-600">${formatCurrency(item.price)}</td>
            <td class="px-6 py-4 text-sm">${item.stock_quantity}</td>
            <td class="px-6 py-4 text-sm">${item.expiry_date || '—'}</td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs rounded-full ${item.is_available ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'}">
                    ${item.is_available ? 'متاح' : 'غير متاح'}
                </span>
            </td>
            <td class="px-6 py-4">
                <button type="button" onclick='editItem(${JSON.stringify(item)})' class="text-emerald-600 text-sm ml-2">تعديل</button>
                <button type="button" onclick="deleteItem(${item.id})" class="text-red-600 text-sm">حذف</button>
            </td>
        </tr>
    `).join('');
}

function openAddModal() {
    const active = categoriesCache.filter(c => c.is_active !== false);
    if (!active.length) {
        if (confirm('يجب إنشاء تصنيف واحد على الأقل قبل إضافة دواء.\n\nهل تريد إنشاء تصنيف الآن؟')) {
            openCategoryModal(true);
        }
        return;
    }

    document.getElementById('itemId').value = '';
    document.getElementById('medicine_id').value = '';
    document.getElementById('modalTitle').textContent = 'إضافة دواء';
    document.getElementById('medicineFields').classList.remove('hidden');
    ['name_ar','generic_name','barcode','dosage_form','strength','price','notes'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('stock_quantity').value = '0';
    document.getElementById('expiry_date').value = '';
    document.getElementById('is_available').checked = true;
    document.getElementById('suggestions').classList.add('hidden');
    clearFieldErrors();
    document.getElementById('itemModal').classList.remove('hidden');
    document.getElementById('itemModal').classList.add('flex');
}

function editItem(item) {
    document.getElementById('itemId').value = item.id;
    document.getElementById('modalTitle').textContent = 'تعديل: ' + item.name_ar;
    document.getElementById('medicineFields').classList.add('hidden');
    document.getElementById('price').value = item.price;
    document.getElementById('stock_quantity').value = item.stock_quantity;
    document.getElementById('expiry_date').value = item.expiry_date || '';
    document.getElementById('notes').value = item.notes || '';
    document.getElementById('is_available').checked = item.is_available;
    clearFieldErrors();
    document.getElementById('itemModal').classList.remove('hidden');
    document.getElementById('itemModal').classList.add('flex');
}

function closeModal() {
    document.getElementById('itemModal').classList.add('hidden');
    document.getElementById('itemModal').classList.remove('flex');
}

async function saveItem(e) {
    e.preventDefault();
    clearFieldErrors();
    const id = document.getElementById('itemId').value;
    if (id) {
        const body = {
            price: parseFloat(document.getElementById('price').value),
            stock_quantity: parseInt(document.getElementById('stock_quantity').value),
            expiry_date: document.getElementById('expiry_date').value || null,
            is_available: document.getElementById('is_available').checked,
            notes: document.getElementById('notes').value || null,
        };
        const data = await apiCall(`/pharmacy/api/medicines/${id}`, { method: 'PUT', body: JSON.stringify(body) });
        if (data?.success) { closeModal(); loadItems(); showSuccess(data.message || 'تم الحفظ'); }
        else handleApiError(data);
    } else {
        const catId = document.getElementById('medicine_category_id').value;
        if (!catId) {
            showError('اختر تصنيفاً أو أنشئ تصنيفاً جديداً');
            return;
        }
        const body = {
            medicine_id: document.getElementById('medicine_id').value ? parseInt(document.getElementById('medicine_id').value) : null,
            medicine_category_id: parseInt(catId),
            name_ar: document.getElementById('name_ar').value.trim(),
            generic_name: document.getElementById('generic_name').value.trim() || null,
            barcode: document.getElementById('barcode').value.trim() || null,
            dosage_form: document.getElementById('dosage_form').value.trim() || null,
            strength: document.getElementById('strength').value.trim() || null,
            price: parseFloat(document.getElementById('price').value),
            stock_quantity: parseInt(document.getElementById('stock_quantity').value),
            expiry_date: document.getElementById('expiry_date').value || null,
            is_available: document.getElementById('is_available').checked,
            notes: document.getElementById('notes').value || null,
        };
        const data = await apiCall('/pharmacy/api/medicines', { method: 'POST', body: JSON.stringify(body) });
        if (data?.success) { closeModal(); loadItems(); showSuccess(data.message || 'تم الإضافة'); }
        else handleApiError(data);
    }
}

async function deleteItem(id) {
    if (!await confirmAction('حذف هذا الدواء من صيدليتك؟')) return;
    const data = await apiCall(`/pharmacy/api/medicines/${id}`, { method: 'DELETE' });
    if (data?.success) loadItems();
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('ar-IQ', { style: 'currency', currency: 'IQD', minimumFractionDigits: 0 }).format(amount);
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('#suggestions') && !e.target.closest('#name_ar')) {
        document.getElementById('suggestions').classList.add('hidden');
    }
});
</script>
@endsection
