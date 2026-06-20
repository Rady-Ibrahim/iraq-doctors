<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل حساب طبيب - أطباء العراق</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-teal-50 to-cyan-100 min-h-screen py-10">
    <div class="bg-white rounded-2xl shadow-xl p-8 w-full max-w-2xl mx-4 md:mx-auto">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-r from-teal-600 to-cyan-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-user-md text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">تسجيل حساب طبيب</h1>
            <p class="text-gray-500 mt-2">أكمل بياناتك وسيتم مراجعة حسابك من قبل الإدارة</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg text-sm mb-6">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('doctor.register') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">الاسم الكامل</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">رقم الهاتف</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                        placeholder="07xxxxxxxxx">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">البريد الإلكتروني</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">التخصص</label>
                    <select name="speciality_id" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                        <option value="">اختر التخصص</option>
                        @foreach ($specialities as $speciality)
                            <option value="{{ $speciality->id }}" @selected(old('speciality_id') == $speciality->id)>
                                {{ $speciality->name_ar }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">كلمة المرور</label>
                    <input type="password" name="password" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">تأكيد كلمة المرور</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">سنوات الخبرة</label>
                    <input type="number" name="experience_years" value="{{ old('experience_years', 0) }}" min="0" max="60"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">صورة الترخيص (PDF أو صورة)</label>
                    <input type="file" name="license_document" accept=".pdf,.jpg,.jpeg,.png" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                </div>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">نبذة عن الطبيب</label>
                <textarea name="bio_ar" rows="3"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">{{ old('bio_ar') }}</textarea>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">صورة العيادة (اختياري)</label>
                <input type="file" name="clinic_image" accept=".jpg,.jpeg,.png"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
            </div>

            <button type="submit"
                class="w-full bg-gradient-to-r from-teal-600 to-cyan-600 text-white py-3 rounded-lg font-semibold hover:from-teal-700 hover:to-cyan-700 transition">
                إنشاء الحساب
            </button>
        </form>

        <div class="mt-6 text-center text-sm">
            <span class="text-gray-500">لديك حساب بالفعل؟</span>
            <a href="{{ route('doctor.login') }}" class="text-teal-600 hover:text-teal-700 font-semibold">تسجيل الدخول</a>
        </div>
    </div>
</body>
</html>
