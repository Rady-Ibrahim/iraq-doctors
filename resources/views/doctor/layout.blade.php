<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'لوحة تحكم الطبيب')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body {
            font-family: 'Cairo', sans-serif;
        }
        .sidebar-link.active {
            background: linear-gradient(90deg, #14b8a6 0%, #06b6d4 100%);
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
                    <div class="w-10 h-10 bg-gradient-to-r from-teal-600 to-cyan-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-user-md text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-gray-800">أطباء العراق</h1>
                        <p class="text-xs text-gray-500">لوحة تحكم الطبيب</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
                <a href="/doctor/dashboard" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-lg transition">
                    <i class="fas fa-home w-5"></i>
                    <span>الرئيسية</span>
                </a>
                <a href="/doctor/dashboard/patients" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition text-gray-700">
                    <i class="fas fa-users w-5"></i>
                    <span>المرضى</span>
                </a>
                <a href="/doctor/dashboard/prescriptions" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition text-gray-700">
                    <i class="fas fa-prescription w-5"></i>
                    <span>الوصفات</span>
                </a>
                <a href="/doctor/dashboard/records" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition text-gray-700">
                    <i class="fas fa-file-medical w-5"></i>
                    <span>السجلات الطبية</span>
                </a>
                <a href="/doctor/dashboard/calendar" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition text-gray-700">
                    <i class="fas fa-calendar-alt w-5"></i>
                    <span>التقويم</span>
                </a>
                <a href="/doctor/dashboard/settings" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition text-gray-700">
                    <i class="fas fa-cog w-5"></i>
                    <span>الإعدادات</span>
                </a>
            </nav>

            <!-- User Info -->
            <div class="p-4 border-t">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-teal-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-teal-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800 text-sm" id="doctorName">د. الاسم</p>
                        <p class="text-xs text-gray-500">طبيب</p>
                    </div>
                </div>
                <button onclick="logout()" class="w-full flex items-center justify-center gap-2 px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>تسجيل الخروج</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">@yield('page-title', 'الرئيسية')</h2>
                        <p class="text-sm text-gray-500">@yield('page-description', 'نظرة عامة على حسابك')</p>
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
            <i class="fas fa-spinner fa-spin text-4xl text-teal-600 mb-4"></i>
            <p class="text-gray-700">جاري التحميل...</p>
        </div>
    </div>

    <script>
        const API_URL = 'http://127.0.0.1:8000';

        function getDoctorToken() {
            return localStorage.getItem('doctor_token');
        }

        function getDoctorUser() {
            return JSON.parse(localStorage.getItem('doctor_user') || '{}');
        }

        function logout() {
            localStorage.removeItem('doctor_token');
            localStorage.removeItem('doctor_user');
            window.location.href = '/doctor/login';
        }

        function showLoading() {
            document.getElementById('loadingOverlay').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').classList.add('hidden');
        }

        async function refreshData() {
            // Override in pages
            location.reload();
        }

        // Set doctor name
        window.addEventListener('load', function() {
            const doctorUser = getDoctorUser();
            if (doctorUser.name) {
                document.getElementById('doctorName').textContent = `د. ${doctorUser.name}`;
            }
        });

        // API Helper
        async function apiCall(endpoint, options = {}) {
            const token = getDoctorToken();
            if (!token) {
                logout();
                return;
            }

            const response = await fetch(`${API_URL}${endpoint}`, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`,
                    ...options.headers
                }
            });

            if (response.status === 401) {
                logout();
                return;
            }

            return response.json();
        }
    </script>
</body>
</html>
