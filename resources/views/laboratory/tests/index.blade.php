@extends('laboratory.layout')

@section('title', 'تحاليل المعمل')
@section('page-title', 'تحاليل المعمل')
@section('page-description', 'اختر التحاليل من كتالوج النظام وحدّد السعر ومدة النتيجة — يختارها المريض عند الطلب')

@section('content')
<div class="flex flex-wrap gap-4 justify-between items-center mb-6">
    <div class="flex gap-3 flex-1">
        <select id="filterCategory" onchange="loadItems()" class="px-3 py-2 border rounded-lg text-sm bg-white">
            <option value="">كل التصنيفات</option>
        </select>
        <input type="text" id="searchInput" placeholder="بحث في تحاليلك..." oninput="loadItems()"
            class="flex-1 min-w-[200px] px-3 py-2 border rounded-lg text-sm">
    </div>
    <button onclick="openAddModal()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
        <i class="fas fa-plus ml-1"></i> إضافة تحليل
    </button>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">التحليل</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">التصنيف</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">السعر</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">مدة النتيجة</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">الحالة</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">إجراءات</th>
            </tr>
        </thead>
        <tbody id="itemsBody">
            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">جاري التحميل...</td></tr>
        </tbody>
    </table>
</div>

<div id="itemModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex justify-between items-center">
            <h3 class="text-lg font-bold" id="modalTitle">إضافة تحليل</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="itemForm" onsubmit="saveItem(event)" class="p-6 space-y-4">
            <input type="hidden" id="itemId">
            <div id="catalogPicker">
                <label class="block text-sm font-semibold mb-1">التصنيف (للبحث)</label>
                <select id="catalogCategory" onchange="loadCatalog()" class="w-full px-4 py-2 border rounded-lg mb-3">
                    <option value="">كل التصنيفات</option>
                </select>
                <label class="block text-sm font-semibold mb-1">اختر التحليل من الكتالوج</label>
                <select id="lab_test_id" required class="w-full px-4 py-2 border rounded-lg">
                    <option value="">جاري التحميل...</option>
                </select>
                <span class="text-red-500 text-sm" data-error-for="lab_test_id"></span>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">السعر (د.ع)</label>
                <input type="number" id="price" required min="0" step="1" class="w-full px-4 py-2 border rounded-lg">
                <span class="text-red-500 text-sm" data-error-for="price"></span>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">مدة النتيجة (ساعات)</label>
                <input type="number" id="result_hours" required min="1" max="720" value="24" class="w-full px-4 py-2 border rounded-lg">
                <p class="text-xs text-gray-500 mt-1">مثال: 24 = خلال يوم، 2 = خلال ساعتين</p>
                <span class="text-red-500 text-sm" data-error-for="result_hours"></span>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">ملاحظات (اختياري)</label>
                <textarea id="notes" rows="2" class="w-full px-4 py-2 border rounded-lg"></textarea>
            </div>
            <label class="flex items-center gap-2" id="availableRow">
                <input type="checkbox" id="is_available" checked class="rounded text-indigo-600">
                <span class="text-sm">متاح للطلب</span>
            </label>
            <button type="submit" class="w-full py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">حفظ</button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
let catalogCache = [];

window.addEventListener('load', async () => {
    await loadCategories();
    await loadItems();
});

async function loadCategories() {
    const data = await apiCall('/laboratory/api/tests/categories');
    if (!data?.success) return;
    const opts = data.data.map(c => `<option value="${c.id}">${c.name_ar}</option>`).join('');
    document.getElementById('filterCategory').innerHTML = '<option value="">كل التصنيفات</option>' + opts;
    document.getElementById('catalogCategory').innerHTML = '<option value="">كل التصنيفات</option>' + opts;
}

async function loadItems() {
    const params = new URLSearchParams();
    const cat = document.getElementById('filterCategory').value;
    const search = document.getElementById('searchInput').value;
    if (cat) params.set('category_id', cat);
    if (search) params.set('search', search);

    const data = await apiCall(`/laboratory/api/tests?${params}`);
    const tbody = document.getElementById('itemsBody');
    if (!data?.success || !data.data?.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">لم تُضف تحاليل بعد. اضغط «إضافة تحليل» لاختيارها من الكتالوج.</td></tr>';
        return;
    }

    tbody.innerHTML = data.data.map(item => `
        <tr class="border-b hover:bg-gray-50">
            <td class="px-6 py-4">
                <p class="font-semibold">${item.name_ar}</p>
                ${item.code ? `<p class="text-xs text-gray-500">${item.code}</p>` : ''}
            </td>
            <td class="px-6 py-4 text-sm">${item.category_name || '-'}</td>
            <td class="px-6 py-4 font-semibold text-indigo-600">${formatCurrency(item.price)}</td>
            <td class="px-6 py-4 text-sm">${item.result_hours} ساعة</td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs rounded-full ${item.is_available ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'}">
                    ${item.is_available ? 'متاح' : 'غير متاح'}
                </span>
            </td>
            <td class="px-6 py-4">
                <button onclick='editItem(${JSON.stringify(item)})' class="text-indigo-600 text-sm ml-2">تعديل</button>
                <button onclick="deleteItem(${item.id})" class="text-red-600 text-sm">حذف</button>
            </td>
        </tr>
    `).join('');
}

async function loadCatalog() {
    const params = new URLSearchParams();
    const cat = document.getElementById('catalogCategory').value;
    if (cat) params.set('category_id', cat);
    const data = await apiCall(`/laboratory/api/tests/catalog?${params}`);
    const select = document.getElementById('lab_test_id');
    if (!data?.success || !data.data?.length) {
        select.innerHTML = '<option value="">لا توجد تحاليل في الكتالوج — تواصل مع الإدارة</option>';
        return;
    }
    catalogCache = data.data;
    select.innerHTML = '<option value="">اختر التحليل</option>' + data.data.map(t =>
        `<option value="${t.id}">${t.name_ar}${t.code ? ' (' + t.code + ')' : ''} — ${t.category_name || ''}</option>`
    ).join('');
}

function openAddModal() {
    document.getElementById('itemId').value = '';
    document.getElementById('modalTitle').textContent = 'إضافة تحليل من الكتالوج';
    document.getElementById('catalogPicker').classList.remove('hidden');
    document.getElementById('lab_test_id').disabled = false;
    document.getElementById('price').value = '';
    document.getElementById('result_hours').value = '24';
    document.getElementById('notes').value = '';
    document.getElementById('is_available').checked = true;
    clearFieldErrors();
    loadCatalog();
    document.getElementById('itemModal').classList.remove('hidden');
    document.getElementById('itemModal').classList.add('flex');
}

function editItem(item) {
    document.getElementById('itemId').value = item.id;
    document.getElementById('modalTitle').textContent = 'تعديل: ' + item.name_ar;
    document.getElementById('catalogPicker').classList.add('hidden');
    document.getElementById('price').value = item.price;
    document.getElementById('result_hours').value = item.result_hours;
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
            result_hours: parseInt(document.getElementById('result_hours').value),
            is_available: document.getElementById('is_available').checked,
            notes: document.getElementById('notes').value || null,
        };
        const data = await apiCall(`/laboratory/api/tests/${id}`, { method: 'PUT', body: JSON.stringify(body) });
        if (data?.success) { closeModal(); loadItems(); }
        else handleApiError(data);
    } else {
        const body = {
            lab_test_id: parseInt(document.getElementById('lab_test_id').value),
            price: parseFloat(document.getElementById('price').value),
            result_hours: parseInt(document.getElementById('result_hours').value),
            is_available: document.getElementById('is_available').checked,
            notes: document.getElementById('notes').value || null,
        };
        const data = await apiCall('/laboratory/api/tests', { method: 'POST', body: JSON.stringify(body) });
        if (data?.success) { closeModal(); loadItems(); }
        else handleApiError(data);
    }
}

async function deleteItem(id) {
    if (!await confirmAction('حذف هذا التحليل من معملك؟')) return;
    const data = await apiCall(`/laboratory/api/tests/${id}`, { method: 'DELETE' });
    if (data?.success) loadItems();
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('ar-IQ', { style: 'currency', currency: 'IQD', minimumFractionDigits: 0 }).format(amount);
}
</script>
@endsection
