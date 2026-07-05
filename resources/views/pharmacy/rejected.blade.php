<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تم رفض الحساب - الصيدلية</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
    <div class="bg-white rounded-2xl shadow-lg max-w-lg w-full p-8">
        <div class="text-center mb-6">
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-times-circle text-red-600 text-3xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">تم رفض طلب تسجيل الصيدلية</h1>
            <p class="text-gray-600">يرجى مراجعة سبب الرفض وإعادة رفع المستندات المطلوبة.</p>
        </div>

        @if ($pharmacy->reject_reason)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <p class="text-sm font-semibold text-red-800 mb-1">سبب الرفض:</p>
                <p class="text-red-700">{{ $pharmacy->reject_reason }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('pharmacy.resubmit') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">السجل التجاري *</label>
                <input type="file" name="commercial_register_document" required accept=".pdf,.jpg,.jpeg,.png" class="w-full px-4 py-2 border rounded-lg">
                @error('commercial_register_document')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">ترخيص الصيدلية *</label>
                <input type="file" name="license_document" required accept=".pdf,.jpg,.jpeg,.png" class="w-full px-4 py-2 border rounded-lg">
                @error('license_document')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">هوية المالك *</label>
                <input type="file" name="owner_id_document" required accept=".pdf,.jpg,.jpeg,.png" class="w-full px-4 py-2 border rounded-lg">
                @error('owner_id_document')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">شعار الصيدلية (اختياري)</label>
                <input type="file" name="logo" accept=".jpg,.jpeg,.png" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <button type="submit" class="w-full px-4 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">إعادة إرسال المستندات</button>
        </form>

        <form method="POST" action="{{ route('pharmacy.logout') }}" class="mt-4">
            @csrf
            <button type="submit" class="w-full px-4 py-3 bg-gray-200 text-gray-700 rounded-lg">تسجيل الخروج</button>
        </form>
    </div>
</body>
</html>
