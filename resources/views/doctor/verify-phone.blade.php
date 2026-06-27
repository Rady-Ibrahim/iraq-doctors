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
<body class="bg-gradient-to-br from-teal-50 to-cyan-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md mx-4">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-gradient-to-r from-teal-600 to-cyan-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-mobile-alt text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">تفعيل رقم الهاتف</h1>
            <p class="text-gray-500 mt-2 text-sm">سيتم إرسال كود التحقق عبر SMS إلى</p>
            <p class="text-teal-700 font-semibold mt-1" dir="ltr">{{ $maskedPhone }}</p>
            <p class="text-gray-400 text-xs mt-1" dir="ltr">{{ $phoneE164 }}</p>
        </div>

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm mb-4">{{ session('success') }}</div>
        @endif
        @if (session('info'))
            <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg text-sm mb-4">{{ session('info') }}</div>
        @endif
        @if (session('warning'))
            <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg text-sm mb-4">{{ session('warning') }}</div>
        @endif
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg text-sm mb-4">
                {{ $errors->first() }}
            </div>
        @endif

        <div id="firebase-error" class="hidden bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg text-sm mb-4"></div>
        <div id="firebase-info" class="hidden bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg text-sm mb-4"></div>

        @if (!$firebaseConfigured)
            <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg text-sm mb-4">
                أضف <code class="text-xs">FIREBASE_WEB_API_KEY</code> في ملف <code class="text-xs">.env</code>
                (من Firebase Console → Project settings → Web API Key).
                <code class="text-xs">project_id</code> و <code class="text-xs">auth_domain</code> يُقرآن تلقائياً من ملف credentials.
            </div>
        @else
            <div id="recaptcha-container" class="mb-4 flex justify-center"></div>

            <button type="button" id="btn-send-otp"
                class="w-full mb-4 bg-gray-100 text-gray-800 py-3 rounded-lg font-semibold hover:bg-gray-200 transition disabled:opacity-50">
                إرسال كود SMS
            </button>

            <div id="otp-section" class="hidden space-y-4">
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">كود التحقق (6 أرقام)</label>
                    <input type="text" id="otp-code" maxlength="6" inputmode="numeric" autocomplete="one-time-code"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 text-center text-2xl tracking-widest"
                        placeholder="000000">
                </div>
                <button type="button" id="btn-verify-otp"
                    class="w-full bg-gradient-to-r from-teal-600 to-cyan-600 text-white py-3 rounded-lg font-semibold hover:from-teal-700 hover:to-cyan-700 transition disabled:opacity-50">
                    تأكيد التفعيل
                </button>
            </div>

            <form id="verify-form" method="POST" action="{{ route('doctor.verify-phone.submit') }}" class="hidden">
                @csrf
                <input type="hidden" name="firebase_token" id="firebase_token">
            </form>
        @endif

        <form method="POST" action="{{ route('doctor.logout') }}" class="mt-6 text-center">
            @csrf
            <button type="submit" class="text-gray-500 hover:text-gray-700 text-sm">تسجيل الخروج</button>
        </form>
    </div>

@if ($firebaseConfigured)
<script src="https://www.gstatic.com/firebasejs/10.14.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.14.0/firebase-auth-compat.js"></script>
<script>
(function () {
    const phoneE164 = @json($phoneE164);
    const firebaseConfig = {
        apiKey: @json(config('firebase.web_api_key')),
        authDomain: @json(config('firebase.auth_domain')),
        projectId: @json(config('firebase.project_id')),
    };

    firebase.initializeApp(firebaseConfig);

    const btnSend = document.getElementById('btn-send-otp');
    const btnVerify = document.getElementById('btn-verify-otp');
    const otpSection = document.getElementById('otp-section');
    const otpInput = document.getElementById('otp-code');
    const errBox = document.getElementById('firebase-error');
    const infoBox = document.getElementById('firebase-info');
    const verifyForm = document.getElementById('verify-form');
    const tokenInput = document.getElementById('firebase_token');

    let confirmationResult = null;
    const recaptchaVerifier = new firebase.auth.RecaptchaVerifier('recaptcha-container', {
        size: 'normal',
        callback: function () {
            btnSend.disabled = false;
        },
    });

    function showError(msg) {
        errBox.textContent = msg;
        errBox.classList.remove('hidden');
        infoBox.classList.add('hidden');
    }

    function showInfo(msg) {
        infoBox.textContent = msg;
        infoBox.classList.remove('hidden');
        errBox.classList.add('hidden');
    }

    btnSend.addEventListener('click', async function () {
        btnSend.disabled = true;
        errBox.classList.add('hidden');

        try {
            confirmationResult = await firebase.auth().signInWithPhoneNumber(phoneE164, recaptchaVerifier);
            otpSection.classList.remove('hidden');
            showInfo('تم إرسال كود التحقق إلى هاتفك عبر SMS.');
            btnSend.textContent = 'إعادة إرسال الكود';
            btnSend.disabled = false;
        } catch (e) {
            console.error(e);
            showError(e.message || 'فشل إرسال كود SMS. تحقق من إعدادات Firebase ورقم الاختبار.');
            btnSend.disabled = false;
            try { recaptchaVerifier.render().then(w => w.reset()); } catch (_) {}
        }
    });

    btnVerify.addEventListener('click', async function () {
        const code = (otpInput.value || '').trim();
        if (code.length !== 6) {
            showError('أدخل كود مكون من 6 أرقام');
            return;
        }
        if (!confirmationResult) {
            showError('اضغط إرسال كود SMS أولاً');
            return;
        }

        btnVerify.disabled = true;

        try {
            const result = await confirmationResult.confirm(code);
            const idToken = await result.user.getIdToken();
            tokenInput.value = idToken;
            verifyForm.submit();
        } catch (e) {
            console.error(e);
            showError(e.message || 'كود التحقق غير صحيح');
            btnVerify.disabled = false;
        }
    });
})();
</script>
@endif
</body>
</html>
