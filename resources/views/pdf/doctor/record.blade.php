<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>سجل طبي #{{ $record['id'] }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        @page { margin: 18px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            direction: ltr;
            text-align: right;
            color: #111827;
            font-size: 12px;
            line-height: 1.6;
        }
        .page {
            border: 2px solid #0f766e;
            border-radius: 10px;
            padding: 22px;
            position: relative;
        }
        .page::before {
            content: '';
            position: absolute;
            top: 6px;
            left: 6px;
            right: 6px;
            bottom: 6px;
            border: 1px solid #99f6e4;
            border-radius: 8px;
            pointer-events: none;
        }
        .inner { position: relative; z-index: 1; }

        table.header { width: 100%; border-collapse: collapse; }
        table.header td { vertical-align: middle; padding: 0; }
        .brand-box { text-align: left; }
        .brand { font-size: 15px; font-weight: bold; color: #0f766e; letter-spacing: 1px; }
        .brand-sub { font-size: 9px; color: #6b7280; }
        .doctor-name { font-size: 17px; font-weight: bold; color: #0f172a; }
        .doctor-spec { font-size: 11px; color: #0f766e; font-weight: bold; }
        .doctor-contact { font-size: 10px; color: #374151; margin-top: 3px; }

        .divider {
            border-bottom: 2px solid #0f766e;
            margin: 14px 0 12px;
            position: relative;
        }
        .divider::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: -4px;
            border-bottom: 1px solid #99f6e4;
        }

        .title-row { text-align: center; margin-bottom: 14px; }
        .rx-title { font-size: 20px; font-weight: bold; color: #0f172a; }
        .rx-symbol { font-size: 24px; color: #0f766e; font-weight: bold; margin-top: 2px; }

        table.info { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.info td { padding: 4px 6px; font-size: 11px; }
        .info-label { color: #6b7280; }
        .info-value { font-weight: bold; color: #0f172a; }

        .badge {
            display: inline-block;
            background: #0f766e;
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            border-radius: 4px;
            padding: 2px 10px;
        }

        .boxed {
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 12px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #0f766e;
            border-bottom: 1px solid #99f6e4;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }
        .body-text { font-size: 11px; color: #1f2937; white-space: pre-wrap; }
        .desc { min-height: 48px; }

        table.attach { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.attach td {
            font-size: 11px;
            padding: 5px 8px;
            border: 1px solid #e5e7eb;
        }
        table.attach .col-num { width: 8%; text-align: center; }
        table.attach .col-name { width: 30%; }
        table.attach .col-size { width: 20%; text-align: center; }

        table.sign { width: 100%; border-collapse: collapse; margin-top: 26px; }
        table.sign td { vertical-align: bottom; padding: 0 6px; }
        .sign-block { text-align: right; }
        .sign-line { width: 140px; border-bottom: 1px solid #374151; margin: 18px 0 2px; }
        .sign-label { font-size: 10px; color: #6b7280; }
        .stamp-block { text-align: left; }
        .stamp {
            display: inline-block;
            border: 2px solid #0f766e;
            border-radius: 6px;
            color: #0f766e;
            font-size: 10px;
            font-weight: bold;
            padding: 6px 10px;
            margin-top: 14px;
            text-align: center;
        }

        .footer {
            margin-top: 18px;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
            text-align: center;
            color: #9ca3af;
            font-size: 9px;
        }

        .print-toolbar { text-align: center; margin-bottom: 14px; }
        .print-toolbar button {
            font-family: 'Cairo', 'Segoe UI', sans-serif;
            background: #0f766e;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 8px 22px;
            font-size: 13px;
            cursor: pointer;
        }
        .print-toolbar button:hover { background: #115e59; }
        @media print {
            .print-toolbar { display: none; }
            body { background: #fff; }
        }
    </style>
</head>
<body>
    <div class="inner">
        @if (!empty($print))
        <div class="print-toolbar">
            <button onclick="window.print()">طباعة السجل</button>
        </div>
        @endif

        <div class="page">
            <div class="inner">
                <!-- Header -->
                <table class="header">
                    <tr>
                        <td>
                            <div class="doctor-name">{{ $doctor->user?->name ?? 'دكتور' }}</div>
                            <div class="doctor-spec">{{ $doctor->speciality?->name ?? '' }}</div>
                            <div class="doctor-contact">
                                @if ($doctor->user?->phone) {{ $doctor->user->phone }} @endif
                                @if ($doctor->address) • {{ $doctor->address }} @endif
                            </div>
                        </td>
                        <td class="brand-box">
                            <div class="brand">أطباء العراق</div>
                            <div class="brand-sub">Iraq Doctors</div>
                        </td>
                    </tr>
                </table>

                <div class="divider"></div>

                <!-- Title -->
                <div class="title-row">
                    <div class="rx-title">سجل طبي</div>
                    <div class="rx-symbol"><span class="badge">{{ $typeLabel }}</span></div>
                </div>

                <!-- Info -->
                <table class="info">
                    <tr>
                        <td><span class="info-label">رقم السجل:</span> <span class="info-value">#{{ $record['id'] }}</span></td>
                        <td><span class="info-label">تاريخ الإنشاء:</span> <span class="info-value">{{ \Carbon\Carbon::parse($record['created_at'])->format('Y-m-d') }}</span></td>
                    </tr>
                    <tr>
                        <td><span class="info-label">اسم المريض:</span> <span class="info-value">{{ $record['patient_name'] ?? '-' }}</span></td>
                        <td><span class="info-label">هاتف المريض:</span> <span class="info-value" dir="ltr">{{ $record['patient_phone'] ?? '-' }}</span></td>
                    </tr>
                    @if (!empty($record['updated_at']) && $record['updated_at'] != $record['created_at'])
                    <tr>
                        <td colspan="2"><span class="info-label">آخر تحديث:</span> <span class="info-value">{{ \Carbon\Carbon::parse($record['updated_at'])->format('Y-m-d') }}</span></td>
                    </tr>
                    @endif
                </table>

                <!-- Title -->
                @if (!empty($record['title']))
                <div class="boxed">
                    <div class="section-title">العنوان</div>
                    <div class="body-text">{{ $record['title'] }}</div>
                </div>
                @endif

                <!-- Description -->
                @if (!empty($record['description']))
                <div class="boxed">
                    <div class="section-title">الوصف</div>
                    <div class="body-text desc">{{ $record['description'] }}</div>
                </div>
                @endif

                <!-- Notes -->
                @if (!empty($record['notes']))
                <div class="boxed">
                    <div class="section-title">ملاحظات</div>
                    <div class="body-text">{{ $record['notes'] }}</div>
                </div>
                @endif

                <!-- Attachments -->
                @if (!empty($record['attachments']))
                <div class="boxed">
                    <div class="section-title">الملفات المرفقة</div>
                    <table class="attach">
                        @foreach ($record['attachments'] as $index => $att)
                            <tr>
                                <td class="col-num">{{ $index + 1 }}</td>
                                <td class="col-name">{{ $att['file_name'] ?? 'ملف' }}</td>
                                <td class="col-size">{{ $att['file_size'] > 0 ? \App\Support\PdfReport::formatFileSize($att['file_size']) : '-' }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                @endif

                <!-- Signature -->
                <table class="sign">
                    <tr>
                        <td>
                            <div class="sign-block">
                                <div class="sign-line"></div>
                                <div class="sign-label">توقيع الطبيب</div>
                            </div>
                        </td>
                        <td>
                            <div class="stamp-block">
                                <div class="stamp">{{ $doctor->user?->name ?? 'دكتور' }}</div>
                            </div>
                        </td>
                    </tr>
                </table>

                <div class="footer">وثيقة طبية صادرة عن نظام أطباء العراق — {{ now()->format('Y-m-d H:i') }}</div>
            </div>
        </div>
    </div>

    @if (!empty($print))
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 350);
        });
    </script>
    @endif
</body>
</html>
