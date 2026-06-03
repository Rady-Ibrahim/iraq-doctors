<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - لوحة تحكم الطبيب</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body {
            font-family: 'Cairo', sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-teal-50 to-cyan-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md mx-4">
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-r from-teal-600 to-cyan-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-user-md text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">لوحة تحكم الطبيب</h1>
            <p class="text-gray-500 mt-2">قم بتسجيل الدخول للمتابعة</p>
        </div>

        <!-- Login Form -->
        <form id="loginForm" class="space-y-6">
            <!-- Phone Number -->
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">رقم الهاتف</label>
                <div class="relative">
                    <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                        <i class="fas fa-phone"></i>
                    </span>
                    <input type="tel" id="phone" name="phone" required
                        class="w-full pr-10 pl-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent transition"
                        placeholder="07xxxxxxxxx">
                </div>
            </div>

            <!-- Password -->
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">كلمة المرور</label>
                <div class="relative">
                    <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" id="password" name="password" required
                        class="w-full pr-10 pl-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent transition"
                        placeholder="••••••••">
                    <button type="button" onclick="togglePassword()" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <input type="checkbox" class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                    <span class="mr-2 text-sm text-gray-600">تذكرني</span>
                </label>
                <a href="#" class="text-sm text-teal-600 hover:text-teal-700">نسيت كلمة المرور؟</a>
            </div>

            <!-- Error Message -->
            <div id="errorMessage" class="hidden bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg text-sm"></div>

            <!-- Login Button -->
            <button type="submit" id="loginBtn"
                class="w-full bg-gradient-to-r from-teal-600 to-cyan-600 text-white py-3 rounded-lg font-semibold hover:from-teal-700 hover:to-cyan-700 transition transform hover:scale-[1.02] active:scale-[0.98]">
                <span id="btnText">تسجيل الدخول</span>
                <i class="fas fa-spinner fa-spin ml-2 hidden" id="loadingIcon"></i>
            </button>
        </form>

        <!-- Footer -->
        <div class="mt-6 text-center text-sm text-gray-500">
            <p>© 2024 أطباء العراق. جميع الحقوق محفوظة.</p>
        </div>
    </div>

    <script>
        const API_URL = 'http://127.0.0.1:8000';

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const phone = document.getElementById('phone').value;
            const password = document.getElementById('password').value;
            const loginBtn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            const loadingIcon = document.getElementById('loadingIcon');
            const errorMessage = document.getElementById('errorMessage');

            // Show loading
            loginBtn.disabled = true;
            btnText.textContent = 'جاري تسجيل الدخول...';
            loadingIcon.classList.remove('hidden');
            errorMessage.classList.add('hidden');

            try {
                const response = await fetch(`${API_URL}/api/v1/auth/login`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ phone, password })
                });

                const data = await response.json();

                if (data.success) {
                    // Check if user is doctor
                    if (data.data.user.role !== 'doctor') {
                        throw new Error('غير مصرح لك بالدخول إلى لوحة تحكم الطبيب');
                    }

                    // Store token
                    localStorage.setItem('doctor_token', data.data.token);
                    localStorage.setItem('doctor_user', JSON.stringify(data.data.user));

                    // Redirect to dashboard
                    window.location.href = '/doctor/dashboard';
                } else {
                    throw new Error(data.error?.message || 'فشل تسجيل الدخول');
                }
            } catch (error) {
                errorMessage.textContent = error.message;
                errorMessage.classList.remove('hidden');
            } finally {
                loginBtn.disabled = false;
                btnText.textContent = 'تسجيل الدخول';
                loadingIcon.classList.add('hidden');
            }
        });

        // Check if already logged in
        window.addEventListener('load', function() {
            const token = localStorage.getItem('doctor_token');
            if (token) {
                window.location.href = '/doctor/dashboard';
            }
        });
    </script>
</body>
</html>
