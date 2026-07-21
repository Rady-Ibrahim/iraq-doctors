<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفعيل رقم الهاتف - أطباء العراق</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-emerald-50 to-green-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md mx-4">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-gradient-to-r from-emerald-600 to-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fab fa-whatsapp text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">تفعيل رقم الهاتف</h1>
            <p class="text-gray-500 mt-2 text-sm">كود التحقق عبر واتساب إلى</p>
            <p class="text-emerald-700 font-semibold mt-1" dir="ltr">{{ $maskedPhone }}</p>
            <p class="text-gray-400 text-xs mt-1" dir="ltr">{{ $phoneE164 }}</p>
        </div>

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm mb-4">{{ session('success') }}</div>
        @endif
        @if (session('info'))
            <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg text-sm mb-4">{{ session('info') }}</div>
        @endif
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg text-sm mb-4">{{ $errors->first() }}</div>
        @endif

        @if (!$wasenderReady)
            <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg text-sm mb-4 space-y-2">
                <p class="font-semibold">WasenderAPI غير مفعّل بعد</p>
                <p class="text-xs">ضع <code>WASENDER_API_KEY</code> في <code>.env</code> لإرسال واتساب حقيقي. حالياً يُسجَّل الكود في اللوج (محلي).</p>
            </div>
        @endif

        <form method="POST" action="{{ route('pharmacy.verify-phone.send') }}" class="mb-4">
            @csrf
            <button type="submit"
                class="w-full bg-gray-100 text-gray-800 py-3 rounded-lg font-semibold hover:bg-gray-200 transition">
                إرسال كود واتساب
            </button>
        </form>

        <form method="POST" action="{{ route('pharmacy.verify-phone.submit') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">كود التحقق (6 أرقام)</label>
                <input type="text" name="code" maxlength="6" inputmode="numeric" autocomplete="one-time-code"
                    value="{{ old('code') }}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 text-center text-2xl tracking-widest"
                    placeholder="000000" required>
            </div>
            <button type="submit"
                class="w-full bg-gradient-to-r from-emerald-600 to-green-600 text-white py-3 rounded-lg font-semibold hover:from-emerald-700 hover:to-green-700 transition">
                تأكيد التفعيل
            </button>
        </form>

        <form method="POST" action="{{ route('pharmacy.logout') }}" class="mt-6 text-center">
            @csrf
            <button type="submit" class="text-gray-500 hover:text-gray-700 text-sm">تسجيل الخروج</button>
        </form>
    </div>
</body>
</html>
