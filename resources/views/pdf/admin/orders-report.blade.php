@extends('pdf.layout')

@section('content')
<table class="cards">
    <tr>
        <td>
            <div class="card">
                <div class="label">طلبات المختبرات</div>
                <div class="value">{{ $report['laboratory']['total'] ?? 0 }}</div>
                <div class="muted">مكتملة: {{ $report['laboratory']['delivered'] ?? 0 }}</div>
            </div>
        </td>
        <td>
            <div class="card">
                <div class="label">طلبات الصيدليات</div>
                <div class="value">{{ $report['pharmacy']['total'] ?? 0 }}</div>
                <div class="muted">مكتملة: {{ $report['pharmacy']['completed'] ?? 0 }}</div>
            </div>
        </td>
        <td>
            <div class="card">
                <div class="label">إجمالي قيمة الطلبات المكتملة</div>
                <div class="value">{{ \App\Support\PdfReport::formatMoney($report['combined_revenue'] ?? 0) }}</div>
                <div class="muted">مختبر + صيدلية</div>
            </div>
        </td>
    </tr>
</table>

@php
    $labRows = collect($report['laboratory']['by_status'] ?? [])->filter(fn ($r) => ($r['count'] ?? 0) > 0);
    $pharmacyRows = collect($report['pharmacy']['by_status'] ?? [])->filter(fn ($r) => ($r['count'] ?? 0) > 0);
@endphp

<h2>توزيع طلبات المختبرات</h2>
<table class="data">
    <thead>
        <tr>
            <th>الحالة</th>
            <th>العدد</th>
        </tr>
    </thead>
    <tbody>
        @forelse($labRows as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td>{{ $row['count'] }}</td>
            </tr>
        @empty
            <tr><td colspan="2" class="muted">لا توجد بيانات</td></tr>
        @endforelse
    </tbody>
</table>
<p class="muted">إيرادات المختبرات المكتملة: {{ \App\Support\PdfReport::formatMoney($report['laboratory']['revenue'] ?? 0) }}</p>

<h2>توزيع طلبات الصيدليات</h2>
<table class="data">
    <thead>
        <tr>
            <th>الحالة</th>
            <th>العدد</th>
        </tr>
    </thead>
    <tbody>
        @forelse($pharmacyRows as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td>{{ $row['count'] }}</td>
            </tr>
        @empty
            <tr><td colspan="2" class="muted">لا توجد بيانات</td></tr>
        @endforelse
    </tbody>
</table>
<p class="muted">إيرادات الصيدليات المكتملة: {{ \App\Support\PdfReport::formatMoney($report['pharmacy']['revenue'] ?? 0) }}</p>
@endsection
