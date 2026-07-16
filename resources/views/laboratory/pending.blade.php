<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حساب قيد المراجعة - المختبر</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
    <div class="bg-white rounded-2xl shadow-lg max-w-lg w-full p-8 text-center">
        <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-clock text-yellow-600 text-3xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-3">حساب المختبر قيد المراجعة</h1>
        <p class="text-gray-600 mb-6">فريق الإدارة يراجع مستنداتك وسيتم إشعارك عند الموافقة.</p>
        @if (session('success'))
            <div class="mb-4 p-3 bg-green-50 text-green-700 rounded-lg">{{ session('success') }}</div>
        @endif
        <div class="bg-gray-50 rounded-lg p-4 text-right mb-6">
            <p class="text-sm text-gray-600">المختبر: <span class="font-semibold text-gray-800">{{ $laboratory->name }}</span></p>
            <p class="text-sm text-gray-600 mt-1">المسؤول: <span class="font-semibold text-gray-800">{{ auth()->user()->name }}</span></p>
            <p class="text-sm text-gray-600 mt-1">المحافظة: <span class="font-semibold text-gray-800">{{ $laboratory->governorate?->name_ar ?? '-' }}</span></p>
            <p class="text-sm text-gray-600 mt-1">الحالة: <span class="font-semibold text-yellow-700">قيد المراجعة</span></p>
        </div>
        <form method="POST" action="{{ route('laboratory.logout') }}">
            @csrf
            <button type="submit" class="w-full px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">تسجيل الخروج</button>
        </form>
    </div>
</body>
</html>
