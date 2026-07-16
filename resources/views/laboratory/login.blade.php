<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.web-auth-csrf', ['csrfRefreshUrl' => '/laboratory/api/csrf-token'])
    <title>تسجيل الدخول - لوحة تحكم المختبر</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 to-violet-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md mx-4">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-r from-indigo-600 to-violet-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-flask text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">لوحة تحكم المختبر</h1>
            <p class="text-gray-500 mt-2">قم بتسجيل الدخول للمتابعة</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg text-sm mb-6">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('laboratory.login') }}" class="space-y-6">
            @csrf
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">رقم الهاتف</label>
                <input type="tel" name="phone" value="{{ old('phone') }}" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                    placeholder="07xxxxxxxxx">
                @error('phone')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">كلمة المرور</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                @error('password')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <label class="flex items-center">
                <input type="checkbox" name="remember" value="1" class="w-4 h-4 text-indigo-600 rounded">
                <span class="mr-2 text-sm text-gray-600">تذكرني</span>
            </label>
            <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-violet-600 text-white py-3 rounded-lg font-semibold">
                تسجيل الدخول
            </button>
        </form>

        <div class="mt-6 text-center text-sm">
            <span class="text-gray-500">ليس لديك حساب؟</span>
            <a href="{{ route('laboratory.register') }}" class="text-indigo-600 font-semibold">سجّل مختبرك</a>
        </div>
    </div>
</body>
</html>
