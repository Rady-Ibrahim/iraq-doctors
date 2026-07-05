@extends('pharmacy.layout')

@section('title', 'مخزون الأدوية')
@section('page-title', 'مخزون الأدوية')
@section('page-description', 'اختر الأدوية من كتالوج النظام وحدّد السعر والكمية المتوفرة — يعرضها المريض عند البحث')

@section('content')
<div class="flex flex-wrap gap-4 justify-between items-center mb-6">
    <div class="flex gap-3 flex-1">
        <select id="filterCategory" onchange="loadItems()" class="px-3 py-2 border rounded-lg text-sm bg-white">
            <option value="">كل التصنيفات</option>
        </select>
        <input type="text" id="searchInput" placeholder="بحث في أدويتك..." oninput="loadItems()"
            class="flex-1 min-w-[200px] px-3 py-2 border rounded-lg text-sm">
    </div>
    <button onclick="openAddModal()" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
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

<div id="itemModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex justify-between items-center">
            <h3 class="text-lg font-bold" id="modalTitle">إضافة دواء</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="itemForm" onsubmit="saveItem(event)" class="p-6 space-y-4">
            <input type="hidden" id="itemId">
            <div id="catalogPicker">
                <label class="block text-sm font-semibold mb-1">التصنيف (للبحث)</label>
                <select id="catalogCategory" onchange="loadCatalog()" class="w-full px-4 py-2 border rounded-lg mb-3">
                    <option value="">كل التصنيفات</option>
                </select>
                <label class="block text-sm font-semibold mb-1">اختر الدواء من الكتالوج</label>
                <select id="medicine_id" required class="w-full px-4 py-2 border rounded-lg">
                    <option value="">جاري التحميل...</option>
                </select>
                <span class="text-red-500 text-sm" data-error-for="medicine_id"></span>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">السعر (د.ع)</label>
                <input type="number" id="price" required min="0" step="1" class="w-full px-4 py-2 border rounded-lg">
                <span class="text-red-500 text-sm" data-error-for="price"></span>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">الكمية في المخزون</label>
                <input type="number" id="stock_quantity" required min="0" max="999999" value="0" class="w-full px-4 py-2 border rounded-lg">
                <p class="text-xs text-gray-500 mt-1">عدد الوحدات المتوفرة حالياً في الصيدلية</p>
                <span class="text-red-500 text-sm" data-error-for="stock_quantity"></span>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">تاريخ انتهاء الصلاحية (اختياري)</label>
                <input type="date" id="expiry_date" class="w-full px-4 py-2 border rounded-lg">
                <p class="text-xs text-gray-500 mt-1">يُستخدم لتنبيهات المخزون في لوحة التحكم</p>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">ملاحظات (اختياري)</label>
                <textarea id="notes" rows="2" class="w-full px-4 py-2 border rounded-lg" placeholder="مثال: يتطلب وصفة طبية"></textarea>
            </div>
            <label class="flex items-center gap-2" id="availableRow">
                <input type="checkbox" id="is_available" checked class="rounded text-emerald-600">
                <span class="text-sm">متاح للطلب</span>
            </label>
            <button type="submit" class="w-full py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">حفظ</button>
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
    const data = await apiCall('/pharmacy/api/medicines/categories');
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

    const data = await apiCall(`/pharmacy/api/medicines?${params}`);
    const tbody = document.getElementById('itemsBody');
    if (!data?.success || !data.data?.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">لم تُضف أدوية بعد. اضغط «إضافة دواء» لاختيارها من الكتالوج.</td></tr>';
        return;
    }

    tbody.innerHTML = data.data.map(item => `
        <tr class="border-b hover:bg-gray-50">
            <td class="px-6 py-4">
                <p class="font-semibold">${item.name_ar}</p>
                ${item.generic_name ? `<p class="text-xs text-gray-500">${item.generic_name}</p>` : ''}
                ${item.barcode ? `<p class="text-xs text-gray-400">${item.barcode}</p>` : ''}
            </td>
            <td class="px-6 py-4 text-sm">${item.category_name || '-'}</td>
            <td class="px-6 py-4 font-semibold text-emerald-600">${formatCurrency(item.price)}</td>
            <td class="px-6 py-4 text-sm">
                <span class="${item.stock_quantity <= 0 ? 'text-red-600 font-semibold' : ''}">${item.stock_quantity}</span>
            </td>
            <td class="px-6 py-4 text-sm">
                ${item.expiry_date
                    ? `<span class="${isExpiryAlert(item.expiry_date) ? 'text-red-600 font-semibold' : ''}">${item.expiry_date}</span>`
                    : '<span class="text-gray-400">—</span>'}
            </td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs rounded-full ${item.is_available ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'}">
                    ${item.is_available ? 'متاح' : 'غير متاح'}
                </span>
            </td>
            <td class="px-6 py-4">
                <button onclick='editItem(${JSON.stringify(item)})' class="text-emerald-600 text-sm ml-2">تعديل</button>
                <button onclick="deleteItem(${item.id})" class="text-red-600 text-sm">حذف</button>
            </td>
        </tr>
    `).join('');
}

async function loadCatalog() {
    const params = new URLSearchParams();
    const cat = document.getElementById('catalogCategory').value;
    if (cat) params.set('category_id', cat);
    const data = await apiCall(`/pharmacy/api/medicines/catalog?${params}`);
    const select = document.getElementById('medicine_id');
    if (!data?.success || !data.data?.length) {
        select.innerHTML = '<option value="">لا توجد أدوية في الكتالوج — تواصل مع الإدارة</option>';
        return;
    }
    catalogCache = data.data;
    select.innerHTML = '<option value="">اختر الدواء</option>' + data.data.map(m => {
        const details = [m.generic_name, m.strength, m.dosage_form].filter(Boolean).join(' — ');
        return `<option value="${m.id}">${m.name_ar}${details ? ' (' + details + ')' : ''} — ${m.category_name || ''}</option>`;
    }).join('');
}

function openAddModal() {
    document.getElementById('itemId').value = '';
    document.getElementById('modalTitle').textContent = 'إضافة دواء من الكتالوج';
    document.getElementById('catalogPicker').classList.remove('hidden');
    document.getElementById('medicine_id').disabled = false;
    document.getElementById('price').value = '';
    document.getElementById('stock_quantity').value = '0';
    document.getElementById('expiry_date').value = '';
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
        if (data?.success) { closeModal(); loadItems(); }
        else handleApiError(data);
    } else {
        const body = {
            medicine_id: parseInt(document.getElementById('medicine_id').value),
            price: parseFloat(document.getElementById('price').value),
            stock_quantity: parseInt(document.getElementById('stock_quantity').value),
            expiry_date: document.getElementById('expiry_date').value || null,
            is_available: document.getElementById('is_available').checked,
            notes: document.getElementById('notes').value || null,
        };
        const data = await apiCall('/pharmacy/api/medicines', { method: 'POST', body: JSON.stringify(body) });
        if (data?.success) { closeModal(); loadItems(); }
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

function isExpiryAlert(dateStr) {
    if (!dateStr) return false;
    const expiry = new Date(dateStr);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const diff = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));
    return diff <= 30;
}
</script>
@endsection
