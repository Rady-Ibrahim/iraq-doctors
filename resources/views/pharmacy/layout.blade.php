<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة تحكم الصيدلية')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
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
        #location-map {
            height: 280px;
            border-radius: 0.5rem;
            z-index: 0;
        }
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
                <a href="{{ route('pharmacy.support') }}" class="{{ $navClass(request()->routeIs('pharmacy.support')) }}">
                    <i class="fas fa-headset w-5"></i>
                    <span>الدعم</span>
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
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">@yield('page-title', 'الرئيسية')</h2>
                        <p class="text-sm text-gray-500">@yield('page-description', '')</p>
                    </div>
                    <!-- Branch Switcher -->
                    <div class="flex items-center gap-3">
                        <div class="relative" id="branchSwitcherWrap">
                            <button onclick="toggleBranchMenu()" id="branchSwitcherBtn"
                                class="flex items-center gap-2 px-3 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 text-sm text-gray-700 transition">
                                <i class="fas fa-code-branch text-emerald-600"></i>
                                <span id="activeBranchName">الفرع الرئيسي</span>
                                <i class="fas fa-chevron-down text-xs text-gray-400"></i>
                            </button>
                            <div id="branchMenu"
                                class="hidden absolute left-0 mt-1 w-56 bg-white border border-gray-200 rounded-xl shadow-lg z-50 overflow-hidden">
                                <p class="px-4 py-2 text-xs text-gray-400 border-b">اختر الفرع</p>
                                <div id="branchMenuList" class="max-h-60 overflow-y-auto">
                                    <p class="px-4 py-3 text-sm text-gray-500">جاري التحميل...</p>
                                </div>
                                <a href="{{ route('pharmacy.branches') }}"
                                    class="flex items-center gap-2 px-4 py-2 text-xs text-emerald-600 hover:bg-emerald-50 border-t">
                                    <i class="fas fa-plus"></i> إدارة الفروع
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6">
                @yield('content')
            </main>
        </div>
    </div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @yield('scripts')
    <script>
        // ── Branch Switcher ────────────────────────────────────────────
        let allBranches = [];
        let activeBranchId = null;

        async function loadBranchSwitcher() {
            try {
                const data = await apiCall('/pharmacy/api/branches');
                if (!data?.success) return;
                allBranches = data.data || [];
                const primary = allBranches.find(b => b.is_primary) || allBranches[0];
                if (primary) setActiveBranch(primary.id, primary.branch_name, false);
                renderBranchMenuList();
            } catch(e) {}
        }

        function renderBranchMenuList() {
            const list = document.getElementById('branchMenuList');
            if (!list) return;
            if (!allBranches.length) {
                list.innerHTML = '<p class="px-4 py-3 text-sm text-gray-500">لا توجد فروع</p>';
                return;
            }
            list.innerHTML = allBranches.map(b => `
                <button onclick="setActiveBranch(${b.id}, '${b.branch_name.replace(/'/g, "\\'")}', true)"
                    class="w-full text-right flex items-center gap-2 px-4 py-2 text-sm hover:bg-emerald-50 transition
                        ${b.id === activeBranchId ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'text-gray-700'}">
                    <i class="fas fa-code-branch text-xs ${b.id === activeBranchId ? 'text-emerald-600' : 'text-gray-400'}"></i>
                    <span class="flex-1">${b.branch_name}</span>
                    ${b.is_primary ? '<span class="text-xs bg-emerald-100 text-emerald-600 px-1.5 py-0.5 rounded">رئيسي</span>' : ''}
                </button>
            `).join('');
        }

        function setActiveBranch(id, name, reload = true) {
            activeBranchId = id;
            const nameEl = document.getElementById('activeBranchName');
            if (nameEl) nameEl.textContent = name;
            // Store in sessionStorage so page reloads keep selection
            sessionStorage.setItem('pharmacy_active_branch', JSON.stringify({ id, name }));
            closeBranchMenu();
            renderBranchMenuList();
            if (reload) {
                // Dispatch event so pages can react without full reload
                window.dispatchEvent(new CustomEvent('branchChanged', { detail: { id, name } }));
            }
        }

        function toggleBranchMenu() {
            const menu = document.getElementById('branchMenu');
            menu?.classList.toggle('hidden');
        }

        function closeBranchMenu() {
            document.getElementById('branchMenu')?.classList.add('hidden');
        }

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!document.getElementById('branchSwitcherWrap')?.contains(e.target)) {
                closeBranchMenu();
            }
        });

        // ── Init ───────────────────────────────────────────────────────
        window.addEventListener('load', async function() {
            // Restore previous branch selection
            const saved = sessionStorage.getItem('pharmacy_active_branch');
            if (saved) {
                const { id, name } = JSON.parse(saved);
                activeBranchId = id;
                const nameEl = document.getElementById('activeBranchName');
                if (nameEl) nameEl.textContent = name;
            }

            loadBranchSwitcher();

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
