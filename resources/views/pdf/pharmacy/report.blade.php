@extends('pdf.layout')

@section('content')
<table class="cards">
    <tr>
        <td>
            <div class="card">
                <div class="label">طلبات نشطة</div>
                <div class="value">{{ $metrics['active_orders'] ?? 0 }}</div>
            </div>
        </td>
        <td>
            <div class="card">
                <div class="label">مكتملة</div>
                <div class="value">{{ $metrics['completed_total'] ?? 0 }}</div>
                <div class="muted">هذا الشهر: {{ $metrics['completed_this_month'] ?? 0 }}</div>
            </div>
        </td>
        <td>
            <div class="card">
                <div class="label">إيرادات الشهر</div>
                <div class="value">{{ \App\Support\PdfReport::formatMoney($metrics['revenue_this_month'] ?? 0) }}</div>
                <div class="muted">الإجمالي: {{ \App\Support\PdfReport::formatMoney($metrics['revenue_total'] ?? 0) }}</div>
            </div>
        </td>
    </tr>
</table>

<h2>توزيع الطلبات حسب الحالة</h2>
<table class="data">
    <thead>
        <tr>
            <th>الحالة</th>
            <th>العدد</th>
        </tr>
    </thead>
    <tbody>
        @forelse(($metrics['orders_by_status'] ?? []) as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td>{{ $row['count'] }}</td>
            </tr>
        @empty
            <tr><td colspan="2" class="muted">لا توجد بيانات</td></tr>
        @endforelse
    </tbody>
</table>

@if (!empty($metrics['stock_alerts']))
    <h2>تنبيهات المخزون</h2>
    <p class="muted">
        نفاد: {{ $metrics['stock_alerts']['out_of_stock_count'] ?? 0 }} —
        منخفض: {{ $metrics['stock_alerts']['low_stock_count'] ?? 0 }}
    </p>
@endif

<h2>سجل الطلبات المكتملة</h2>
<table class="data">
    <thead>
        <tr>
            <th>رقم الطلب</th>
            <th>المريض</th>
            <th>التنفيذ</th>
            <th>الأدوية</th>
            <th>المبلغ</th>
            <th>تاريخ الإكمال</th>
        </tr>
    </thead>
    <tbody>
        @forelse(($history ?? []) as $row)
            <tr>
                <td>{{ $row['order_number'] }}</td>
                <td>{{ $row['patient_name'] ?? '-' }}</td>
                <td>{{ $row['fulfillment_label'] ?? '-' }}</td>
                <td>{{ $row['items_count'] ?? 0 }}</td>
                <td>{{ \App\Support\PdfReport::formatMoney($row['total_amount'] ?? 0) }}</td>
                <td>{{ $row['completed_at'] ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">لا توجد طلبات مكتملة</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
