<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل صيدلية - أطباء العراق</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body { font-family: 'Cairo', sans-serif; }
        #pharmacy-map { height: 280px; border-radius: 0.5rem; z-index: 0; }
    </style>
</head>
<body class="bg-gradient-to-br from-emerald-50 to-green-100 min-h-screen py-10">
    <div class="bg-white rounded-2xl shadow-xl p-8 w-full max-w-3xl mx-4 md:mx-auto">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-r from-emerald-600 to-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-pills text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">تسجيل صيدلية</h1>
            <p class="text-gray-500 mt-2">أكمل البيانات وسيتم مراجعة حسابك من قبل الإدارة</p>
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

        <form method="POST" action="{{ route('pharmacy.register') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div>
                <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">بيانات المسؤول والصيدلية</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">اسم المسؤول</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-4 py-3 border rounded-lg">
                        @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">اسم الصيدلية</label>
                        <input type="text" name="pharmacy_name" value="{{ old('pharmacy_name') }}" required class="w-full px-4 py-3 border rounded-lg">
                        @error('pharmacy_name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">رقم الهاتف</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" required class="w-full px-4 py-3 border rounded-lg">
                        @error('phone')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">البريد (اختياري)</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="w-full px-4 py-3 border rounded-lg">
                        @error('email')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">كلمة المرور</label>
                        <input type="password" name="password" required class="w-full px-4 py-3 border rounded-lg">
                        @error('password')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">تأكيد كلمة المرور</label>
                        <input type="password" name="password_confirmation" required class="w-full px-4 py-3 border rounded-lg">
                    </div>
                </div>
                <div class="mt-5">
                    <label class="block text-gray-700 text-sm font-semibold mb-2">نبذة عن الصيدلية (اختياري)</label>
                    <textarea name="description_ar" rows="3" class="w-full px-4 py-3 border rounded-lg">{{ old('description_ar') }}</textarea>
                </div>
            </div>

            <div>
                <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">موقع الصيدلية</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">المحافظة</label>
                        <select name="governorate_id" required class="w-full px-4 py-3 border rounded-lg">
                            <option value="">اختر المحافظة</option>
                            @foreach ($governorates as $governorate)
                                <option value="{{ $governorate->id }}" @selected(old('governorate_id') == $governorate->id)>{{ $governorate->name_ar }}</option>
                            @endforeach
                        </select>
                        @error('governorate_id')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">المنطقة</label>
                        <input type="text" name="area" value="{{ old('area') }}" required class="w-full px-4 py-3 border rounded-lg">
                        @error('area')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mt-5">
                    <label class="block text-gray-700 text-sm font-semibold mb-2">العنوان بالتفصيل</label>
                    <input type="text" name="address" value="{{ old('address') }}" required class="w-full px-4 py-3 border rounded-lg">
                    @error('address')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <p class="text-sm text-gray-500 mt-4 mb-2">اضغط على الخريطة لتحديد الموقع، أو</p>
                <button type="button" onclick="detectLocation()" id="detectLocationBtn"
                    class="mb-3 flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm">
                    <i class="fas fa-location-arrow"></i>
                    <span>تحديد موقعي الحالي</span>
                </button>
                <div id="pharmacy-map" class="border border-gray-300"></div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-4">
                    <input type="number" step="any" name="latitude" id="latitude" value="{{ old('latitude', '33.3152') }}" required readonly class="w-full px-4 py-3 border rounded-lg bg-gray-50">
                    <input type="number" step="any" name="longitude" id="longitude" value="{{ old('longitude', '44.3661') }}" required readonly class="w-full px-4 py-3 border rounded-lg bg-gray-50">
                </div>
            </div>

            <div>
                <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">المستندات</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">شعار الصيدلية *</label>
                        <input type="file" name="logo" accept=".jpg,.jpeg,.png" required class="w-full px-4 py-3 border rounded-lg">
                        @error('logo')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">السجل التجاري *</label>
                        <input type="file" name="commercial_register_document" accept=".pdf,.jpg,.jpeg,.png" required class="w-full px-4 py-3 border rounded-lg">
                        @error('commercial_register_document')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">ترخيص الصيدلية *</label>
                        <input type="file" name="license_document" accept=".pdf,.jpg,.jpeg,.png" required class="w-full px-4 py-3 border rounded-lg">
                        @error('license_document')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">هوية المالك *</label>
                        <input type="file" name="owner_id_document" accept=".pdf,.jpg,.jpeg,.png" required class="w-full px-4 py-3 border rounded-lg">
                        @error('owner_id_document')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-emerald-600 to-green-600 text-white py-3 rounded-lg font-semibold">
                إنشاء حساب الصيدلية
            </button>
        </form>

        <div class="mt-6 text-center text-sm">
            <a href="{{ route('pharmacy.login') }}" class="text-emerald-600 font-semibold">لديك حساب؟ تسجيل الدخول</a>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        const defaultLat = parseFloat(latInput.value) || 33.3152;
        const defaultLng = parseFloat(lngInput.value) || 44.3661;
        const map = L.map('pharmacy-map').setView([defaultLat, defaultLng], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
        let marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
        function updateCoords(lat, lng) {
            latInput.value = lat.toFixed(7);
            lngInput.value = lng.toFixed(7);
        }
        map.on('click', (e) => { marker.setLatLng(e.latlng); updateCoords(e.latlng.lat, e.latlng.lng); });
        marker.on('dragend', (e) => { const p = e.target.getLatLng(); updateCoords(p.lat, p.lng); });

        function detectLocation() {
            const btn = document.getElementById('detectLocationBtn');
            if (!navigator.geolocation) { alert('المتصفح لا يدعم تحديد الموقع'); return; }
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التحديد...';
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    const lat = pos.coords.latitude, lng = pos.coords.longitude;
                    marker.setLatLng([lat, lng]); map.setView([lat, lng], 16); updateCoords(lat, lng);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-location-arrow"></i> تحديد موقعي الحالي';
                },
                function() {
                    alert('تعذر تحديد الموقع. تأكد من منح الإذن للمتصفح.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-location-arrow"></i> تحديد موقعي الحالي';
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }
    </script>
</body>
</html>
