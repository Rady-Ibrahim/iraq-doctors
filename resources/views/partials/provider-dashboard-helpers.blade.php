<script>
function formatCurrency(amount) {
    return new Intl.NumberFormat('ar-IQ', { style: 'currency', currency: 'IQD', minimumFractionDigits: 0 }).format(amount || 0);
}

function providerStatusClass(status) {
    const map = {
        new: 'bg-blue-100 text-blue-800',
        reviewing: 'bg-yellow-100 text-yellow-800',
        quoted: 'bg-purple-100 text-purple-800',
        accepted: 'bg-teal-100 text-teal-800',
        preparing: 'bg-orange-100 text-orange-800',
        out_for_delivery: 'bg-cyan-100 text-cyan-800',
        completed: 'bg-green-100 text-green-800',
        delivered: 'bg-green-100 text-green-800',
        scheduled: 'bg-indigo-100 text-indigo-800',
        collected: 'bg-cyan-100 text-cyan-800',
        processing: 'bg-orange-100 text-orange-800',
        ready: 'bg-lime-100 text-lime-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return map[status] || 'bg-gray-100 text-gray-800';
}

const KPI_THEMES = {
    emerald: { value: 'text-emerald-600', icon: 'bg-emerald-100 text-emerald-600' },
    blue: { value: 'text-blue-600', icon: 'bg-blue-100 text-blue-600' },
    purple: { value: 'text-purple-600', icon: 'bg-purple-100 text-purple-600' },
    orange: { value: 'text-orange-600', icon: 'bg-orange-100 text-orange-600' },
    green: { value: 'text-green-600', icon: 'bg-green-100 text-green-600' },
    teal: { value: 'text-teal-600', icon: 'bg-teal-100 text-teal-600' },
    cyan: { value: 'text-cyan-600', icon: 'bg-cyan-100 text-cyan-600' },
    indigo: { value: 'text-indigo-600', icon: 'bg-indigo-100 text-indigo-600' },
};

const BAR_THEMES = {
    emerald: 'bg-emerald-500',
    indigo: 'bg-indigo-500',
    blue: 'bg-blue-500',
};

function renderKpiCard({ label, value, sub, icon, color = 'emerald', href }) {
    const theme = KPI_THEMES[color] || KPI_THEMES.emerald;
    const inner = `
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm text-gray-500">${label}</p>
                <p class="text-2xl font-bold ${theme.value} mt-1">${value}</p>
                ${sub ? `<p class="text-xs text-gray-500 mt-1">${sub}</p>` : ''}
            </div>
            ${icon ? `<div class="w-11 h-11 rounded-xl ${theme.icon} flex items-center justify-center shrink-0">
                <i class="fas ${icon}"></i>
            </div>` : ''}
        </div>
    `;
    if (href) {
        return `<a href="${href}" class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition block">${inner}</a>`;
    }
    return `<div class="bg-white rounded-xl shadow-sm p-6">${inner}</div>`;
}

function renderStatusBars(ordersByStatus, accentColor = 'emerald') {
    const barClass = BAR_THEMES[accentColor] || BAR_THEMES.emerald;
    const items = (ordersByStatus || []).filter(s => s.count > 0);
    if (!items.length) {
        return '<p class="text-sm text-gray-500">لا توجد طلبات بعد</p>';
    }
    const max = Math.max(...items.map(s => s.count), 1);
    return items.map(s => `
        <div>
            <div class="flex justify-between text-sm mb-1">
                <span>${s.label}</span>
                <span class="font-semibold">${s.count}</span>
            </div>
            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full ${barClass} rounded-full" style="width: ${Math.round((s.count / max) * 100)}%"></div>
            </div>
        </div>
    `).join('');
}

function renderRecentOrdersTable(orders, basePath, emptyMessage = 'لا توجد طلبات نشطة') {
    if (!orders?.length) {
        return `<p class="text-sm text-gray-500 text-center py-8">${emptyMessage}</p>`;
    }
    return `
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-2 text-right font-semibold">الطلب</th>
                    <th class="px-4 py-2 text-right font-semibold">المريض</th>
                    <th class="px-4 py-2 text-right font-semibold">الحالة</th>
                    <th class="px-4 py-2 text-right font-semibold">المبلغ</th>
                    <th class="px-4 py-2 text-right font-semibold"></th>
                </tr>
            </thead>
            <tbody>
                ${orders.map(o => `
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs">${o.order_number}</td>
                        <td class="px-4 py-3">${o.patient_name || '—'}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs ${providerStatusClass(o.status)}">${o.status_label}</span>
                            ${o.awaiting_patient ? '<span class="block text-xs text-purple-600 mt-1">بانتظار المريض</span>' : ''}
                        </td>
                        <td class="px-4 py-3 font-semibold">${o.total_amount ? formatCurrency(o.total_amount) : '—'}</td>
                        <td class="px-4 py-3">
                            <a href="${basePath}/orders/${o.id}" class="text-xs font-semibold hover:underline">عرض</a>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}
</script>
