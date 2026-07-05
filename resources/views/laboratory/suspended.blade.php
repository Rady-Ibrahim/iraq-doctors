<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حساب معلّق - المعمل</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
    <div class="bg-white rounded-2xl shadow-lg max-w-lg w-full p-8 text-center">
        <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-ban text-gray-600 text-3xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-3">تم تعليق حساب المعمل</h1>
        <p class="text-gray-600 mb-6">يرجى التواصل مع الإدارة لمزيد من المعلومات.</p>
        <form method="POST" action="{{ route('laboratory.logout') }}">
            @csrf
            <button type="submit" class="w-full px-4 py-3 bg-gray-200 text-gray-700 rounded-lg">تسجيل الخروج</button>
        </form>
    </div>
</body>
</html>
