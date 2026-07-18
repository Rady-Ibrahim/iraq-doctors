<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'تقرير' }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            direction: rtl;
            text-align: right;
            color: #1f2937;
            font-size: 12px;
            margin: 0;
            padding: 24px;
        }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 18px 0 8px; color: #111827; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        .meta { color: #6b7280; font-size: 11px; margin-bottom: 16px; }
        .header { margin-bottom: 18px; }
        .brand { color: #0f766e; font-size: 13px; font-weight: bold; margin-bottom: 4px; }
        .cards { width: 100%; margin-bottom: 12px; }
        .cards td {
            width: 33.33%;
            vertical-align: top;
            padding: 8px;
        }
        .card {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px;
            background: #f9fafb;
        }
        .card .label { color: #6b7280; font-size: 10px; margin-bottom: 4px; }
        .card .value { font-size: 15px; font-weight: bold; }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.data th, table.data td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
            text-align: right;
            font-size: 11px;
        }
        table.data th { background: #f3f4f6; font-weight: bold; }
        .muted { color: #6b7280; }
        .footer {
            margin-top: 24px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            color: #9ca3af;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">أطباء العراق</div>
        <h1>{{ $title ?? 'تقرير' }}</h1>
        <div class="meta">
            @if (!empty($subtitle))
                {{ $subtitle }} —
            @endif
            تاريخ التصدير: {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>

    @yield('content')

    <div class="footer">تم إنشاء هذا التقرير تلقائياً من نظام أطباء العراق</div>
</body>
</html>
