@extends('admin.layout')

@section('title', 'كتالوج التحاليل')
@section('page-title', 'كتالوج التحاليل')
@section('page-description', 'إدارة تصنيفات وتحاليل النظام المرجعية — يختار منها المعامل')

@section('content')
<div class="mb-6 flex gap-4 border-b">
    <button onclick="showTab('categories')" id="tab-categories" class="px-4 py-3 border-b-2 border-blue-600 text-blue-600 font-semibold">التصنيفات</button>
    <button onclick="showTab('tests')" id="tab-tests" class="px-4 py-3 border-b-2 border-transparent text-gray-600 hover:text-gray-800">التحاليل</button>
</div>

<div id="panel-categories">
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
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
                <input type="text" id="categoryIcon" placeholder="fas fa-vial" class="w-full px-4 py-2 border rounded-lg">
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
                    <th class="px-6 py-3 text-right text-sm font-semibold">عدد التحاليل</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">الحالة</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">إجراءات</th>
                </tr>
            </thead>
            <tbody id="categoriesBody"><tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">جاري التحميل...</td></tr></tbody>
        </table>
    </div>
</div>

<div id="panel-tests" class="hidden">
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">إضافة تحليل</h3>
        <form id="testForm" class="grid grid-cols-1 md:grid-cols-3 gap-4" onsubmit="saveTest(event)">
            <input type="hidden" id="testId">
            <div>
                <label class="block text-sm font-semibold mb-1">التصنيف</label>
                <select id="testCategoryId" required class="w-full px-4 py-2 border rounded-lg"></select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">اسم التحليل (عربي)</label>
                <input type="text" id="testNameAr" required class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">الرمز</label>
                <input type="text" id="testCode" placeholder="CBC" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">نوع العينة</label>
                <input type="text" id="testSampleType" placeholder="دم" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold mb-1">الوصف</label>
                <input type="text" id="testDescription" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div class="md:col-span-3 flex gap-2">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg">حفظ التحليل</button>
                <button type="button" onclick="resetTestForm()" class="px-6 py-2 border rounded-lg text-gray-600">إلغاء</button>
            </div>
        </form>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4 flex gap-4">
        <select id="filterCategory" onchange="loadTests()" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">كل التصنيفات</option>
        </select>
        <input type="text" id="searchTests" placeholder="بحث..." oninput="loadTests()" class="flex-1 px-3 py-2 border rounded-lg text-sm">
    </div>
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-right text-sm font-semibold">التحليل</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">التصنيف</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">الرمز</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">العينة</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">الحالة</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">إجراءات</th>
                </tr>
            </thead>
            <tbody id="testsBody"><tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">جاري التحميل...</td></tr></tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
let categoriesCache = [];

window.addEventListener('load', async () => {
    await loadCategories();
    await loadTests();
});

function showTab(tab) {
    ['categories', 'tests'].forEach(t => {
        document.getElementById('panel-' + t).classList.toggle('hidden', t !== tab);
        const btn = document.getElementById('tab-' + t);
        btn.className = t === tab
            ? 'px-4 py-3 border-b-2 border-blue-600 text-blue-600 font-semibold'
            : 'px-4 py-3 border-b-2 border-transparent text-gray-600 hover:text-gray-800';
    });
}

async function loadCategories() {
    const data = await apiCall('/admin/api/lab-test-categories');
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
            <td class="px-6 py-4">${c.tests_count || 0}</td>
            <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full ${c.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100'}">${c.is_active ? 'نشط' : 'معطّل'}</span></td>
            <td class="px-6 py-4">
                <button onclick='editCategory(${JSON.stringify(c)})' class="text-blue-600 text-sm ml-2">تعديل</button>
                <button onclick="toggleCategory(${c.id}, ${!c.is_active})" class="text-amber-600 text-sm ml-2">${c.is_active ? 'تعطيل' : 'تفعيل'}</button>
                <button onclick="deleteCategory(${c.id})" class="text-red-600 text-sm">حذف</button>
            </td>
        </tr>
    `).join('');
}

function fillCategorySelects() {
    const opts = categoriesCache.map(c => `<option value="${c.id}">${c.name_ar}</option>`).join('');
    document.getElementById('testCategoryId').innerHTML = '<option value="">اختر التصنيف</option>' + opts;
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
    const url = id ? `/admin/api/lab-test-categories/${id}` : '/admin/api/lab-test-categories';
    const data = await apiCall(url, { method: id ? 'PUT' : 'POST', body: JSON.stringify(body) });
    if (data?.success) { resetCategoryForm(); loadCategories(); showSuccess(data.message); }
}

async function toggleCategory(id, isActive) {
    const cat = categoriesCache.find(c => c.id === id);
    if (!cat) return;
    const data = await apiCall(`/admin/api/lab-test-categories/${id}`, {
        method: 'PUT',
        body: JSON.stringify({ name_ar: cat.name_ar, name_en: cat.name_en, icon: cat.icon, is_active: isActive }),
    });
    if (data?.success) loadCategories();
}

async function deleteCategory(id) {
    if (!await confirmAction('حذف هذا التصنيف؟')) return;
    const data = await apiCall(`/admin/api/lab-test-categories/${id}`, { method: 'DELETE' });
    if (data?.success) loadCategories();
    else if (data) alert(data.message || 'تعذر الحذف');
}

async function loadTests() {
    const params = new URLSearchParams();
    const cat = document.getElementById('filterCategory')?.value;
    const search = document.getElementById('searchTests')?.value;
    if (cat) params.set('category_id', cat);
    if (search) params.set('search', search);

    const data = await apiCall(`/admin/api/lab-tests?${params}`);
    const tbody = document.getElementById('testsBody');
    if (!data?.success || !data.data?.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">لا توجد تحاليل</td></tr>';
        return;
    }

    tbody.innerHTML = data.data.map(t => `
        <tr class="border-b hover:bg-gray-50">
            <td class="px-6 py-4 font-semibold">${t.name_ar}</td>
            <td class="px-6 py-4">${t.category_name || '-'}</td>
            <td class="px-6 py-4">${t.code || '-'}</td>
            <td class="px-6 py-4">${t.sample_type || '-'}</td>
            <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full ${t.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100'}">${t.is_active ? 'نشط' : 'معطّل'}</span></td>
            <td class="px-6 py-4">
                <button onclick='editTest(${JSON.stringify(t)})' class="text-blue-600 text-sm ml-2">تعديل</button>
                <button onclick="toggleTest(${t.id}, ${!t.is_active})" class="text-amber-600 text-sm ml-2">${t.is_active ? 'تعطيل' : 'تفعيل'}</button>
                <button onclick="deleteTest(${t.id})" class="text-red-600 text-sm">حذف</button>
            </td>
        </tr>
    `).join('');
}

function resetTestForm() {
    document.getElementById('testId').value = '';
    document.getElementById('testForm').reset();
}

function editTest(t) {
    document.getElementById('testId').value = t.id;
    document.getElementById('testCategoryId').value = t.lab_test_category_id;
    document.getElementById('testNameAr').value = t.name_ar;
    document.getElementById('testCode').value = t.code || '';
    document.getElementById('testSampleType').value = t.sample_type || '';
    document.getElementById('testDescription').value = t.description_ar || '';
    showTab('tests');
}

async function saveTest(e) {
    e.preventDefault();
    const id = document.getElementById('testId').value;
    const body = {
        lab_test_category_id: parseInt(document.getElementById('testCategoryId').value),
        name_ar: document.getElementById('testNameAr').value,
        code: document.getElementById('testCode').value || null,
        sample_type: document.getElementById('testSampleType').value || null,
        description_ar: document.getElementById('testDescription').value || null,
        is_active: true,
    };
    const url = id ? `/admin/api/lab-tests/${id}` : '/admin/api/lab-tests';
    const data = await apiCall(url, { method: id ? 'PUT' : 'POST', body: JSON.stringify(body) });
    if (data?.success) { resetTestForm(); loadTests(); loadCategories(); showSuccess(data.message); }
}

async function toggleTest(id, isActive) {
    const res = await apiCall(`/admin/api/lab-tests?`);
    const test = res?.data?.find(t => t.id === id);
    if (!test) return;
    const data = await apiCall(`/admin/api/lab-tests/${id}`, {
        method: 'PUT',
        body: JSON.stringify({ ...test, lab_test_category_id: test.lab_test_category_id, name_ar: test.name_ar, is_active: isActive }),
    });
    if (data?.success) loadTests();
}

async function deleteTest(id) {
    if (!await confirmAction('حذف هذا التحليل؟')) return;
    const data = await apiCall(`/admin/api/lab-tests/${id}`, { method: 'DELETE' });
    if (data?.success) { loadTests(); loadCategories(); }
    else if (data) alert(data.message || 'تعذر الحذف');
}
</script>
@endsection
