<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة تحكم الإدارة')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body {
            font-family: 'Cairo', sans-serif;
        }
        .sidebar-link.active {
            background: linear-gradient(90deg, #3b82f6 0%, #6366f1 100%);
            color: white;
        }
        .sidebar-link:hover:not(.active) {
            background-color: #f3f4f6;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg flex flex-col">
            <!-- Logo -->
            <div class="p-6 border-b">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-user-shield text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-gray-800">أطباء العراق</h1>
                        <p class="text-xs text-gray-500">لوحة تحكم الإدارة</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
                <a href="/admin/dashboard" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-lg transition">
                    <i class="fas fa-home w-5"></i>
                    <span>الرئيسية</span>
                </a>
                <a href="/admin/dashboard/doctors" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition text-gray-700">
                    <i class="fas fa-user-md w-5"></i>
                    <span>الأطباء</span>
                </a>
                <a href="/admin/dashboard/patients" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition text-gray-700">
                    <i class="fas fa-users w-5"></i>
                    <span>المرضى</span>
                </a>
                <a href="/admin/dashboard/appointments" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition text-gray-700">
                    <i class="fas fa-calendar-check w-5"></i>
                    <span>المواعيد</span>
                </a>
                <a href="/admin/dashboard/revenue" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition text-gray-700">
                    <i class="fas fa-chart-line w-5"></i>
                    <span>الإيرادات</span>
                </a>
                <a href="/admin/dashboard/subscriptions" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition text-gray-700">
                    <i class="fas fa-crown w-5"></i>
                    <span>الاشتراكات</span>
                </a>
                <a href="/admin/dashboard/analytics" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition text-gray-700">
                    <i class="fas fa-chart-pie w-5"></i>
                    <span>التحليلات</span>
                </a>
                <a href="/admin/dashboard/specialities" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition text-gray-700">
                    <i class="fas fa-stethoscope w-5"></i>
                    <span>التخصصات</span>
                </a>
                <a href="/admin/dashboard/governorates" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition text-gray-700">
                    <i class="fas fa-map-marker-alt w-5"></i>
                    <span>المحافظات</span>
                </a>
                <a href="/admin/users" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition text-gray-700">
                    <i class="fas fa-user-cog w-5"></i>
                    <span>إدارة المستخدمين</span>
                </a>
            </nav>

            <!-- User Info -->
            <div class="p-4 border-t">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-blue-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800 text-sm" id="adminName">{{ auth()->user()->name ?? 'مدير النظام' }}</p>
                        <p class="text-xs text-gray-500">مسؤول</p>
                    </div>
                </div>
                <form id="logoutForm" method="POST" action="{{ route('admin.logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>تسجيل الخروج</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">@yield('page-title', 'الرئيسية')</h2>
                        <p class="text-sm text-gray-500">@yield('page-description', 'نظرة عامة على النظام')</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <button class="relative p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                            <i class="fas fa-bell"></i>
                            <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>
                        <button onclick="refreshData()" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg p-8 flex flex-col items-center">
            <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
            <p class="text-gray-700">جاري التحميل...</p>
        </div>
    </div>

    <!-- Error Toast -->
    <div id="errorToast" class="fixed top-4 left-4 bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg hidden z-50">
        <div class="flex items-center gap-3">
            <i class="fas fa-exclamation-circle"></i>
            <span id="errorMessage">حدث خطأ</span>
            <button onclick="hideError()" class="ml-4 hover:text-red-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Success Toast -->
    <div id="successToast" class="fixed top-4 left-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg hidden z-50">
        <div class="flex items-center gap-3">
            <i class="fas fa-check-circle"></i>
            <span id="successMessage">تم بنجاح</span>
            <button onclick="hideSuccess()" class="ml-4 hover:text-green-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <script>
        function getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        }

        function showLoading() {
            document.getElementById('loadingOverlay').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').classList.add('hidden');
        }

        async function refreshData() {
            location.reload();
        }

        @if (session('success'))
            window.addEventListener('load', function() {
                showSuccess(@json(session('success')));
            });
        @endif

        async function apiCall(endpoint, options = {}) {
            const response = await fetch(endpoint, {
                ...options,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    ...options.headers
                }
            });

            if (response.status === 401 || response.status === 419) {
                window.location.href = '{{ route('admin.login') }}';
                return;
            }

            const data = await response.json();

            if (!data.success && data.error) {
                showError(data.error.message || 'حدث خطأ');
            }

            return data;
        }

        // Error Handling
        function showError(message) {
            const errorToast = document.getElementById('errorToast');
            document.getElementById('errorMessage').textContent = message;
            errorToast.classList.remove('hidden');
            setTimeout(() => {
                errorToast.classList.add('hidden');
            }, 5000);
        }

        function hideError() {
            document.getElementById('errorToast').classList.add('hidden');
        }

        function showSuccess(message) {
            const successToast = document.getElementById('successToast');
            document.getElementById('successMessage').textContent = message;
            successToast.classList.remove('hidden');
            setTimeout(() => {
                successToast.classList.add('hidden');
            }, 3000);
        }

        function hideSuccess() {
            document.getElementById('successToast').classList.add('hidden');
        }

        // Form Validation
        function validateForm(formId, rules) {
            const form = document.getElementById(formId);
            if (!form) return false;

            let isValid = true;
            const errors = {};

            for (const [field, rule] of Object.entries(rules)) {
                const input = form.querySelector(`[name="${field}"]`);
                if (!input) continue;

                const value = input.value.trim();
                const errorElement = document.getElementById(`${field}_error`);

                if (rule.required && !value) {
                    errors[field] = `${rule.label || field} مطلوب`;
                    isValid = false;
                } else if (rule.minLength && value.length < rule.minLength) {
                    errors[field] = `${rule.label || field} يجب أن يكون ${rule.minLength} أحرف على الأقل`;
                    isValid = false;
                } else if (rule.email && !isValidEmail(value)) {
                    errors[field] = `${rule.label || field} يجب أن يكون بريد إلكتروني صحيح`;
                    isValid = false;
                } else if (rule.phone && !isValidPhone(value)) {
                    errors[field] = `${rule.label || field} يجب أن يكون رقم هاتف صحيح`;
                    isValid = false;
                } else {
                    delete errors[field];
                }

                if (errorElement) {
                    if (errors[field]) {
                        errorElement.textContent = errors[field];
                        errorElement.classList.remove('hidden');
                        input.classList.add('border-red-500');
                    } else {
                        errorElement.classList.add('hidden');
                        input.classList.remove('border-red-500');
                    }
                }
            }

            return isValid;
        }

        function isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        function isValidPhone(phone) {
            const re = /^[0-9]{10,15}$/;
            return re.test(phone);
        }

        // Clear Form Errors
        function clearFormErrors(formId) {
            const form = document.getElementById(formId);
            if (!form) return;

            const errorElements = form.querySelectorAll('[id$="_error"]');
            errorElements.forEach(el => {
                el.classList.add('hidden');
                el.textContent = '';
            });

            const inputs = form.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                input.classList.remove('border-red-500');
            });
        }
    </script>
    @yield('scripts')
</body>
</html>
