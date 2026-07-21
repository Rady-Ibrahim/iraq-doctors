@extends('pdf.layout')

@section('content')
<table class="cards">
    <tr>
        <td>
            <div class="card">
                <div class="label">إجمالي الإيرادات</div>
                <div class="value">{{ \App\Support\PdfReport::formatMoney($report['total_revenue'] ?? 0) }}</div>
            </div>
        </td>
        <td>
            <div class="card">
                <div class="label">إيرادات الاشتراكات</div>
                <div class="value">{{ \App\Support\PdfReport::formatMoney($report['subscription_revenue'] ?? 0) }}</div>
            </div>
        </td>
        <td>
            <div class="card">
                <div class="label">مواعيد مكتملة</div>
                <div class="value">{{ $report['completed_appointments'] ?? 0 }}</div>
                <div class="muted">متوسط الاشتراك: {{ \App\Support\PdfReport::formatMoney($report['average_revenue'] ?? 0) }}</div>
            </div>
        </td>
    </tr>
</table>

<h2>الإيرادات حسب الفئة</h2>
<table class="data">
    <thead>
        <tr>
            <th>الفئة</th>
            <th>المبلغ</th>
            <th>النسبة</th>
        </tr>
    </thead>
    <tbody>
        @forelse(($report['revenue_by_category'] ?? []) as $row)
            <tr>
                <td>{{ $row['name'] }}</td>
                <td>{{ \App\Support\PdfReport::formatMoney($row['amount'] ?? 0) }}</td>
                <td>{{ $row['percentage'] ?? 0 }}%</td>
            </tr>
        @empty
            <tr><td colspan="3" class="muted">لا توجد بيانات</td></tr>
        @endforelse
    </tbody>
</table>

<h2>أفضل الأطباء أداءً</h2>
<table class="data">
    <thead>
        <tr>
            <th>الطبيب</th>
            <th>المواعيد</th>
            <th>إيراد الاشتراك</th>
        </tr>
    </thead>
    <tbody>
        @forelse(($report['top_performers'] ?? []) as $row)
            <tr>
                <td>{{ $row['name'] ?? '-' }}</td>
                <td>{{ $row['appointments'] ?? 0 }}</td>
                <td>{{ \App\Support\PdfReport::formatMoney($row['revenue'] ?? 0) }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="muted">لا توجد بيانات</td></tr>
        @endforelse
    </tbody>
</table>

<h2>أحدث المعاملات</h2>
<table class="data">
    <thead>
        <tr>
            <th>التاريخ</th>
            <th>الوصف</th>
            <th>المبلغ</th>
        </tr>
    </thead>
    <tbody>
        @forelse(($report['recent_transactions'] ?? []) as $row)
            <tr>
                <td>{{ $row['date'] ?? '-' }}</td>
                <td>{{ $row['description'] ?? '-' }}</td>
                <td>{{ \App\Support\PdfReport::formatMoney($row['amount'] ?? 0) }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="muted">لا توجد بيانات</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
