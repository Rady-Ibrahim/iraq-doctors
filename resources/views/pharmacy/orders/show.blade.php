@extends('pharmacy.layout')

@section('title', 'تفاصيل الطلب')
@section('page-title', 'تفاصيل الطلب')
@section('page-description', 'مراجعة الطلب وعرض السعر ومتابعة الحالة')

@section('content')
<div class="mb-4">
    <a href="{{ route('pharmacy.orders.index') }}" class="text-emerald-600 text-sm hover:underline">
        <i class="fas fa-arrow-right ml-1"></i> العودة للطلبات
    </a>
</div>

<div id="orderContent" class="space-y-6">
    <p class="text-gray-500">جاري التحميل...</p>
</div>

<div id="quoteModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex justify-between items-center">
            <h3 class="text-lg font-bold">عرض السعر — تحديد الأدوية</h3>
            <button onclick="closeQuoteModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="quoteForm" onsubmit="submitQuote(event)" class="p-6 space-y-4">
            <p class="text-sm text-gray-600">اختر الأدوية من كتالوج صيدليتك (خاصة عند وجود صورة روشتة):</p>
            <div id="catalogCheckboxes" class="max-h-60 overflow-y-auto border rounded-lg p-3 space-y-2"></div>
            <span class="text-red-500 text-sm" data-error-for="items"></span>
            <div id="deliveryFeeField" class="hidden">
                <label class="block text-sm font-semibold mb-1">رسوم التوصيل (د.ع)</label>
                <input type="number" id="delivery_fee" min="0" class="w-full px-4 py-2 border rounded-lg">
                <span class="text-red-500 text-sm" data-error-for="delivery_fee"></span>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">ملاحظات للمريض</label>
                <textarea id="quote_notes" rows="2" class="w-full px-4 py-2 border rounded-lg"></textarea>
            </div>
            <button type="submit" class="w-full py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">إرسال عرض السعر</button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const orderId = {{ $orderId }};
let orderData = null;
let catalogItems = [];

window.addEventListener('load', async () => {
    await Promise.all([loadOrder(), loadCatalog()]);
});

async function loadOrder() {
    const data = await apiCall(`/pharmacy/api/orders/${orderId}`);
    const box = document.getElementById('orderContent');
    if (!data?.success) {
        box.innerHTML = '<p class="text-red-600">تعذر تحميل الطلب</p>';
        return;
    }
    orderData = data.data;
    renderOrder();
}

async function loadCatalog() {
    const data = await apiCall('/pharmacy/api/medicines');
    if (data?.success) catalogItems = data.data || [];
}

function renderOrder() {
    const o = orderData;
    const canReview = o.status === 'new';
    const canQuote = ['new', 'reviewing'].includes(o.status);
    const awaitingPatient = o.status === 'quoted' || o.awaiting_patient_acceptance;
    const nextActions = (o.next_statuses || []).filter(s => s.value !== 'cancelled').map(s => `
        <button onclick="transition('${s.value}')" class="px-3 py-1.5 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700">${s.label}</button>
    `).join('');

    const deliveryBlock = o.fulfillment_type === 'delivery' ? `
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm">
            <h4 class="font-semibold text-blue-800 mb-2"><i class="fas fa-truck ml-1"></i> عنوان التوصيل</h4>
            <p class="text-gray-800">${o.delivery_address || '—'}</p>
            ${o.delivery_notes ? `<p class="text-gray-600 mt-2"><strong>ملاحظات التوصيل:</strong> ${o.delivery_notes}</p>` : ''}
        </div>
    ` : '';

    document.getElementById('orderContent').innerHTML = `
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex flex-wrap justify-between gap-4 mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">${o.order_number}</h3>
                            <p class="text-sm text-gray-500">${o.created_at}</p>
                        </div>
                        <div class="flex flex-wrap gap-2 items-start">
                            <span class="px-2 py-1 rounded-full text-xs ${o.fulfillment_type === 'delivery' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700'}">
                                ${o.fulfillment_label || (o.fulfillment_type === 'delivery' ? 'توصيل' : 'استلام')}
                            </span>
                            <span class="px-3 py-1 rounded-full text-sm font-semibold h-fit ${statusClass(o.status)}">${o.status_label}</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><span class="text-gray-500">المريض:</span> <strong>${o.patient_name}</strong></div>
                        <div><span class="text-gray-500">الهاتف:</span> ${o.patient_phone || '—'}</div>
                    </div>
                    ${o.patient_notes ? `<p class="mt-4 text-sm bg-gray-50 p-3 rounded-lg"><strong>ملاحظات المريض:</strong> ${o.patient_notes}</p>` : ''}
                </div>

                ${deliveryBlock}

                ${o.prescription_image ? `
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h4 class="font-semibold mb-3"><i class="fas fa-image text-amber-600 ml-1"></i> صورة الروشتة</h4>
                    <a href="${o.prescription_image}" target="_blank">
                        <img src="${o.prescription_image}" alt="روشتة" class="max-h-80 rounded-lg border">
                    </a>
                </div>` : (o.source === 'prescription' ? `
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
                    طلب بروشتة — لم تُرفع صورة بعد أو بانتظار تحديد الأدوية من الصيدلية.
                </div>` : '')}

                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b font-semibold">الأدوية (${o.items.length})</div>
                    ${o.items.length ? `
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50"><tr>
                            <th class="px-6 py-2 text-right">الدواء</th>
                            <th class="px-6 py-2 text-right">السعر</th>
                            <th class="px-6 py-2 text-right">الكمية</th>
                            <th class="px-6 py-2 text-right">الإجمالي</th>
                        </tr></thead>
                        <tbody>${o.items.map(i => `
                            <tr class="border-b">
                                <td class="px-6 py-3">${i.medicine_name}</td>
                                <td class="px-6 py-3">${formatCurrency(i.price)}</td>
                                <td class="px-6 py-3">${i.quantity}</td>
                                <td class="px-6 py-3 font-semibold">${formatCurrency(i.line_total)}</td>
                            </tr>`).join('')}</tbody>
                    </table>` : '<p class="px-6 py-8 text-center text-gray-500">لا توجد أدوية — حددها عند عرض السعر</p>'}
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h4 class="font-semibold mb-4">الملخص المالي</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span>المجموع الفرعي</span><span>${o.subtotal ? formatCurrency(o.subtotal) : '—'}</span></div>
                        <div class="flex justify-between"><span>رسوم التوصيل</span><span>${o.delivery_fee ? formatCurrency(o.delivery_fee) : (o.fulfillment_type === 'delivery' ? '—' : 'لا ينطبق')}</span></div>
                        <div class="flex justify-between font-bold text-lg border-t pt-2"><span>الإجمالي</span><span class="text-emerald-600">${o.total_amount ? formatCurrency(o.total_amount) : '—'}</span></div>
                    </div>
                    ${o.quote_notes ? `<p class="mt-4 text-xs text-gray-500">${o.quote_notes}</p>` : ''}
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 space-y-3">
                    <h4 class="font-semibold mb-2">الإجراءات</h4>
                    ${awaitingPatient ? `
                    <div class="rounded-lg border border-purple-200 bg-purple-50 p-4 text-sm text-purple-900">
                        <p class="font-semibold mb-1"><i class="fas fa-clock ml-1"></i> بانتظار موافقة المريض على السعر</p>
                        <p class="text-purple-700">تم إرسال عرض السعر للمريض. أكمل التجهيز بعد موافقته من التطبيق.</p>
                    </div>` : ''}
                    ${canReview ? `<button onclick="startReview()" class="w-full py-2 bg-yellow-500 text-white rounded-lg text-sm hover:bg-yellow-600">بدء المراجعة</button>` : ''}
                    ${canQuote ? `<button onclick="openQuoteModal()" class="w-full py-2 bg-emerald-600 text-white rounded-lg text-sm hover:bg-emerald-700">عرض السعر / تحديد الأدوية</button>` : ''}
                    <div class="flex flex-wrap gap-2">${nextActions}</div>
                    ${(o.next_statuses || []).some(s => s.value === 'cancelled') ? `
                        <button onclick="cancelOrder()" class="w-full py-2 border border-red-300 text-red-600 rounded-lg text-sm mt-2 hover:bg-red-50">إلغاء الطلب</button>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}

function openQuoteModal() {
    clearFieldErrors();
    const container = document.getElementById('catalogCheckboxes');
    if (!catalogItems.length) {
        container.innerHTML = '<p class="text-red-600 text-sm">لا توجد أدوية في كتالوج الصيدلية. أضف أدوية أولاً من صفحة الأدوية.</p>';
    } else {
        const selectedIds = new Set((orderData.items || []).map(i => i.pharmacy_medicine_id));
        const availableItems = catalogItems.filter(item => item.is_available);
        container.innerHTML = availableItems.length
            ? availableItems.map(item => `
                <label class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded cursor-pointer">
                    <input type="checkbox" name="catalog_item" value="${item.id}" class="rounded text-emerald-600"
                        ${selectedIds.has(item.id) ? 'checked' : ''}>
                    <span class="flex-1 text-sm">${item.name_ar}${item.strength ? ' — ' + item.strength : ''}${item.stock_quantity <= 0 ? ' <span class="text-red-500">(نفد المخزون)</span>' : ''}</span>
                    <span class="text-emerald-600 text-sm font-semibold">${formatCurrency(item.price)}</span>
                </label>
            `).join('')
            : '<p class="text-red-600 text-sm">لا توجد أدوية متاحة للطلب. فعّل الأدوية من صفحة المخزون.</p>';
    }

    const isDelivery = orderData.fulfillment_type === 'delivery';
    const feeField = document.getElementById('deliveryFeeField');
    feeField.classList.toggle('hidden', !isDelivery);
    if (isDelivery) {
        document.getElementById('delivery_fee').value = orderData.delivery_fee || '';
    }

    document.getElementById('quote_notes').value = orderData.quote_notes || '';
    document.getElementById('quoteModal').classList.remove('hidden');
    document.getElementById('quoteModal').classList.add('flex');
}

function closeQuoteModal() {
    document.getElementById('quoteModal').classList.add('hidden');
    document.getElementById('quoteModal').classList.remove('flex');
}

async function submitQuote(e) {
    e.preventDefault();
    clearFieldErrors();
    const checked = [...document.querySelectorAll('input[name="catalog_item"]:checked')];
    const body = {
        items: checked.map(cb => ({ pharmacy_medicine_id: parseInt(cb.value), quantity: 1 })),
        quote_notes: document.getElementById('quote_notes').value || null,
    };
    if (orderData.fulfillment_type === 'delivery') {
        body.delivery_fee = parseFloat(document.getElementById('delivery_fee').value) || 0;
    }
    const data = await apiCall(`/pharmacy/api/orders/${orderId}/quote`, {
        method: 'POST',
        body: JSON.stringify(body),
    });
    if (data?.success) {
        closeQuoteModal();
        orderData = data.data;
        renderOrder();
        alert(data.message);
    } else {
        handleApiError(data);
    }
}

async function startReview() {
    const data = await apiCall(`/pharmacy/api/orders/${orderId}/review`, { method: 'POST', body: '{}' });
    if (data?.success) { orderData = data.data; renderOrder(); }
    else alert(data?.error?.message || 'تعذر بدء المراجعة');
}

async function transition(status) {
    const body = { status };
    const data = await apiCall(`/pharmacy/api/orders/${orderId}/transition`, {
        method: 'POST',
        body: JSON.stringify(body),
    });
    if (data?.success) { orderData = data.data; renderOrder(); alert(data.message); }
    else alert(data?.error?.message || 'تعذر تغيير الحالة');
}

async function cancelOrder() {
    const reason = prompt('سبب الإلغاء (اختياري):');
    if (reason === null) return;
    const data = await apiCall(`/pharmacy/api/orders/${orderId}/transition`, {
        method: 'POST',
        body: JSON.stringify({ status: 'cancelled', cancel_reason: reason }),
    });
    if (data?.success) { orderData = data.data; renderOrder(); }
    else alert(data?.error?.message || 'تعذر الإلغاء');
}

function statusClass(s) {
    const map = {
        new: 'bg-blue-100 text-blue-800',
        reviewing: 'bg-yellow-100 text-yellow-800',
        quoted: 'bg-purple-100 text-purple-800',
        accepted: 'bg-teal-100 text-teal-800',
        preparing: 'bg-orange-100 text-orange-800',
        out_for_delivery: 'bg-cyan-100 text-cyan-800',
        completed: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return map[s] || 'bg-gray-100 text-gray-800';
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('ar-IQ', { style: 'currency', currency: 'IQD', minimumFractionDigits: 0 }).format(amount);
}
</script>
@endsection
