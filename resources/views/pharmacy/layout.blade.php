<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة تحكم الصيدلية')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @include('partials.dashboard-ui', ['confirmColor' => '#059669'])
    @include('partials.dashboard-api', [
        'loginUrl' => route('pharmacy.login'),
        'csrfRefreshUrl' => '/pharmacy/api/csrf-token',
    ])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body { font-family: 'Cairo', sans-serif; }
        .sidebar-link.active {
            background: linear-gradient(90deg, #059669 0%, #10b981 100%);
            color: white;
        }
        .sidebar-link:hover:not(.active) { background-color: #f3f4f6; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <aside class="w-64 bg-white shadow-lg flex flex-col">
            <div class="p-6 border-b">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-emerald-600 to-green-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-pills text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-gray-800">أطباء العراق</h1>
                        <p class="text-xs text-gray-500">لوحة تحكم الصيدلية</p>
                    </div>
                </div>
            </div>

            @php
                $navClass = fn (bool $active) => $active
                    ? 'sidebar-link active flex items-center gap-3 px-4 py-3 rounded-lg transition'
                    : 'sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition text-gray-700';
            @endphp
            <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
                <a href="{{ route('pharmacy.dashboard') }}" class="{{ $navClass(request()->routeIs('pharmacy.dashboard')) }}">
                    <i class="fas fa-home w-5"></i>
                    <span>الرئيسية</span>
                </a>
                <a href="{{ route('pharmacy.settings') }}" class="{{ $navClass(request()->routeIs('pharmacy.settings')) }}">
                    <i class="fas fa-cog w-5"></i>
                    <span>الإعدادات</span>
                </a>
                <a href="{{ route('pharmacy.branches') }}" class="{{ $navClass(request()->routeIs('pharmacy.branches')) }}">
                    <i class="fas fa-code-branch w-5"></i>
                    <span>الفروع</span>
                </a>
                <a href="{{ route('pharmacy.subscription.plans') }}" class="{{ $navClass(request()->routeIs('pharmacy.subscription.*')) }}">
                    <i class="fas fa-crown w-5"></i>
                    <span>الاشتراك</span>
                </a>
                <a href="{{ route('pharmacy.medicines.index') }}" class="{{ $navClass(request()->routeIs('pharmacy.medicines.*')) }}">
                    <i class="fas fa-capsules w-5"></i>
                    <span>الأدوية</span>
                </a>
                <a href="{{ route('pharmacy.orders.index') }}" class="{{ $navClass(request()->routeIs('pharmacy.orders.*')) }}">
                    <i class="fas fa-clipboard-list w-5"></i>
                    <span>الطلبات</span>
                    <span id="pendingOrdersBadge" class="hidden bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">0</span>
                </a>
                <a href="{{ route('pharmacy.reports') }}" class="{{ $navClass(request()->routeIs('pharmacy.reports')) }}">
                    <i class="fas fa-chart-bar w-5"></i>
                    <span>التقارير والسجل</span>
                </a>
            </nav>

            <div class="p-4 border-t">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-pills text-emerald-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800 text-sm">{{ auth()->user()->name ?? 'الصيدلية' }}</p>
                        <p class="text-xs text-gray-500">صيدلية</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('pharmacy.logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50">
                        <i class="fas fa-sign-out-alt"></i>
                        تسجيل الخروج
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow-sm px-6 py-4">
                <h2 class="text-xl font-bold text-gray-800">@yield('page-title', 'الرئيسية')</h2>
                <p class="text-sm text-gray-500">@yield('page-description', '')</p>
            </header>

            <main class="flex-1 overflow-y-auto p-6">
                @yield('content')
            </main>
        </div>
    </div>
    @yield('scripts')
    <script>
        window.addEventListener('load', async function() {
            try {
                const data = await apiCall('/pharmacy/api/orders?limit=1');
                const badge = document.getElementById('pendingOrdersBadge');
                if (badge && data?.success) {
                    const count = data.data?.counts?.pending || 0;
                    badge.textContent = count;
                    badge.classList.toggle('hidden', count === 0);
                }
            } catch (e) {}
        });
    </script>
</body>
</html>
