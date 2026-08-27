<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Edlink</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="{{ asset('js/chart.umd.js') }}"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    colors: { darken: '#252641' }
                }
            }
        }
    </script>
    <style>
        [x-cloak]{display:none!important;}
        html, body { overflow-x: hidden; max-width: 100vw; }

        :root { --sidebar-w: 288px; }

        #app-sidebar { width: var(--sidebar-w); transition: width .2s ease; }

        #app-main {
            width: 100%;
            min-width: 0;
            transition: margin-left .2s ease, width .2s ease;
        }
        @media (min-width: 1024px) {
            #app-main {
                flex: 0 0 auto;
                width: calc(100% - var(--sidebar-w));
                margin-left: var(--sidebar-w);
            }
        }

        /* Sidebar nav — hover and active states in Edlink yellow */
        .nav-link, .nav-group-btn {
            color: #d1d5db;
            transition: background-color .15s ease, color .15s ease;
        }
        .nav-link:hover, .nav-group-btn:hover {
            background-color: rgba(234, 179, 8, 0.12);
            color: #facc15;
        }
        .nav-link.active {
            background-color: #eab308;
            color: #252641;
            font-weight: 600;
        }
        .nav-link.active svg { color: #252641; }
        .nav-sub-link {
            color: #9ca3af;
            transition: color .15s ease;
        }
        .nav-sub-link:hover { color: #facc15; }
    </style>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('ui', {
                collapsed: localStorage.getItem('edlink_sidebar_collapsed') === 'true',
                toggle() {
                    this.collapsed = !this.collapsed;
                    localStorage.setItem('edlink_sidebar_collapsed', this.collapsed);
                    setTimeout(() => window.dispatchEvent(new Event('resize')), 220);
                }
            });
        });
    </script>
    @livewireStyles
</head>
<body class="font-sans bg-gray-50 min-h-screen flex overflow-x-hidden"
      x-data="{ mobileNavOpen: false }"
      x-init="$watch('$store.ui.collapsed', v => document.documentElement.style.setProperty('--sidebar-w', v ? '80px' : '288px'));
               document.documentElement.style.setProperty('--sidebar-w', $store.ui.collapsed ? '80px' : '288px');">
@include('partials.global-loader')
    <!-- Mobile top bar -->
    <div class="lg:hidden fixed top-0 inset-x-0 bg-darken text-white flex items-center justify-between px-4 py-3 z-30">
        <div>
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2">
            <img src="{{ asset('img/logo.png') }}" alt="Edlink logo" class="w-[180px] h-auto">
            </a>
        </div>
        <button @click="mobileNavOpen = !mobileNavOpen" class="text-xl">☰</button>
    </div>

    <!-- Sidebar -->
    <aside
        id="app-sidebar"
        class="bg-darken text-white flex-col fixed inset-y-0 z-40 transform lg:translate-x-0 flex overflow-y-auto overflow-x-hidden"
        :class="mobileNavOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        x-data="{ open: '' }"
    >
        <div class="px-6 py-6 flex items-center space-x-2 border-b border-white/10 hidden lg:flex" :class="$store.ui.collapsed && 'justify-center px-0'">
            <span class=" flex-shrink-0">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2">
            <img src="{{ asset('img/fav.png') }}" alt="Edlink logo" class="w-[50px] h-auto">
            </a>
            </span>
            <span class="font-semibold text-lg" x-show="!$store.ui.collapsed" x-cloak>
                <div>
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2">
            <img src="{{ asset('img/dash.png') }}" alt="Edlink logo" class="w-[125px] h-auto">
            </a>
        </div>
            </span>
        </div>

        <nav class="flex-1 px-3 py-6 space-y-1 text-sm mt-14 lg:mt-0">

            <a href="{{ route('dashboard') }}" class="nav-link active flex items-center space-x-3 px-3 py-2.5 rounded-lg" :class="$store.ui.collapsed && 'justify-center'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                <span x-show="!$store.ui.collapsed" x-cloak>Dashboard</span>
            </a>

            @foreach([
                'students' => ['label' => 'Students', 'icon' => 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0121 17.5c0 .34-.02.675-.06 1.004M12 14l-6.16-3.422A12.083 12.083 0 003 17.5c0 .34.02.675.06 1.004M12 14v7', 'items' => ['Registration', 'All Students', 'Graduates & Alumni', 'Categories', 'Houses & Clubs', 'Portal Access', 'Promotions', 'ID Cards']],
                'academics' => ['label' => 'Academics', 'icon' => 'M4 6h16M4 12h16M4 18h7', 'items' => ['Classes & Streams', 'Subjects', 'Grading Scales', 'Timetable', 'Homework', 'Events']],
                'attendance' => ['label' => 'Attendance', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'items' => ['Mark Attendance', 'Attendance Reports']],
                'finance' => ['label' => 'Finance', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v2m9-8a9 9 0 11-18 0 9 9 0 0118 0z', 'items' => ['Terms', 'Fee Structure', 'Payments', 'Expenses']],
                'accounting' => ['label' => 'Accounting', 'icon' => 'M4 19.5h16M5.5 17V9.5m4 7.5V9.5m5 7.5V9.5m4 7.5V9.5M3 7l9-4 9 4H3z', 'items' => ['Accounting Workspace', 'Account Reconciliation']],
                'exams' => ['label' => 'Exams', 'icon' => 'M9 17v-2a4 4 0 014-4h4M9 17H7a2 2 0 01-2-2V7a2 2 0 012-2h6l4 4v6a2 2 0 01-2 2h-2', 'items' => ['Create Exam', 'Enter Marks', 'Report Cards']],
                'staff' => ['label' => 'Staff', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-2.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-.001-8.001A4 4 0 0017 8z', 'items' => ['All Staff', 'Add Staff', 'Payroll', 'Leave Requests', 'Designations & Access']],
                'parents' => ['label' => 'Parents', 'icon' => 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l1.586-1.586z', 'items' => ['Register Parent', 'All Parents', 'Communications']],
            ] as $key => $group)
                @continue($key === 'accounting' && ! auth()->user()->hasModuleAccess('accounting'))
                <div>
                    <button @click="if($store.ui.collapsed){$store.ui.toggle();open='{{ $key }}'}else{open = open === '{{ $key }}' ? '' : '{{ $key }}'}" class="nav-group-btn w-full flex items-center px-3 py-2.5 rounded-lg" :class="[$store.ui.collapsed ? 'justify-center' : 'justify-between', open === '{{ $key }}' && !$store.ui.collapsed && 'bg-yellow-500/10 text-yellow-400']">
                        <span class="flex items-center space-x-3" :class="$store.ui.collapsed && 'space-x-0'">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $group['icon'] }}" /></svg>
                            <span x-show="!$store.ui.collapsed" x-cloak>{{ $group['label'] }}</span>
                        </span>
                        <svg x-show="!$store.ui.collapsed" x-cloak class="w-4 h-4 transition-transform" :class="open === '{{ $key }}' && 'rotate-90'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </button>
                    <div x-cloak x-show="open === '{{ $key }}' && !$store.ui.collapsed" class="pl-11 pr-3 py-1 space-y-1">
                        @foreach($group['items'] as $item)
                            @php
                                $itemRoute = match($item) {
                                    'Registration' => 'students.register',
                                    'Classes & Streams' => 'classes.index',
                                    'Subjects' => 'subjects.index',
                                    'Grading Scales' => 'grading-scales.index',
                                    'Promotions' => 'promotions.index',
                                    'All Students' => 'students.index',
                                    'Graduates & Alumni' => 'graduates.index',
                                    'Houses & Clubs' => 'students.activities',
                                    'Categories' => 'student-categories.index',
                                    'Portal Access' => 'students.portal-access',
                                    'ID Cards' => 'students.id-cards',
                                    'Terms' => 'terms.index',
                                    'Fee Structure' => 'fee-structures.index',
                                    'Payments' => 'fee-payments.index',
                                    'Expenses' => 'expenses.index',
                                    'Accounting Workspace' => 'accounting.index',
                                    'Account Reconciliation' => 'accounting.reconciliations',
                                    'Create Exam' => 'exams.setup',
                                    'Enter Marks' => 'exams.marks',
                                    'Report Cards' => 'exams.results',
                                    'Payroll' => 'payroll.index',
                                    'Leave Requests' => 'leaves.index',
                                    'Designations & Access' => 'designations.index',
                                    'Mark Attendance' => 'attendance.index',
                                    'Attendance Reports' => 'attendance.reports',
                                    'Timetable' => 'timetable.index',
                                    'Homework' => 'homework.index',
                                    'Events' => 'events.index',
                                    'All Staff' => 'staff.index',
                                    'Add Staff' => 'staff.register',
                                    'Register Parent' => 'parents.register',
                                    'All Parents' => 'parents.index',
                                    'Communications' => 'communications.index',
                                    default => null,
                                };
                            @endphp
                            @if($itemRoute)
                                <a href="{{ route($itemRoute) }}" wire:navigate class="nav-sub-link {{ request()->routeIs($itemRoute) ? 'active' : '' }} block py-1.5 text-sm">{{ $item }}</a>
                            @else
                                <a href="#" class="nav-sub-link block py-1.5 text-sm">{{ $item }}</a>
                            @endif
                        @endforeach
                </div>
            @endforeach

            <a href="{{ route('reports.index') }}" wire:navigate class="nav-link {{ request()->routeIs('reports.index') ? 'active' : '' }} flex items-center space-x-3 px-3 py-2.5 rounded-lg" :class="$store.ui.collapsed && 'justify-center space-x-0'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                <span x-show="!$store.ui.collapsed" x-cloak>Reports</span>
            </a>
            <a href="#" class="nav-link flex items-center space-x-3 px-3 py-2.5 rounded-lg" :class="$store.ui.collapsed && 'justify-center space-x-0'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
                <span x-show="!$store.ui.collapsed" x-cloak>Announcements</span>
            </a>
        </nav>

        <div class="px-3 py-6 border-t border-white/10 space-y-1">
            <button @click="$store.ui.toggle()" class="nav-link hidden lg:flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm w-full" :class="$store.ui.collapsed && 'justify-center space-x-0'">
                <svg class="w-5 h-5 flex-shrink-0 transition-transform" :class="$store.ui.collapsed && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" /></svg>
                <span x-show="!$store.ui.collapsed" x-cloak>Collapse</span>
            </button>
            <a href="{{ route('settings.index') }}" wire:navigate class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }} flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm" :class="$store.ui.collapsed && 'justify-center space-x-0'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                <span x-show="!$store.ui.collapsed" x-cloak>Settings</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm w-full text-left" :class="$store.ui.collapsed && 'justify-center space-x-0'">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    <span x-show="!$store.ui.collapsed" x-cloak>Log out</span>
                </button>
            </form>
        </div>
    </aside>

    <div x-cloak x-show="mobileNavOpen" @click="mobileNavOpen = false" class="fixed inset-0 bg-black/40 z-30 lg:hidden"></div>

    <!-- Main content -->
    <div id="app-main" class="min-w-0 pt-14 lg:pt-0">

        <!-- Topbar -->
        <header class="bg-white border-b border-gray-200 px-4 sm:px-6 lg:px-10 py-3 flex items-center justify-between gap-3 sticky top-0 z-20 shadow-sm">
            <div class="flex items-center space-x-4 min-w-0">
                <button @click="$store.ui.toggle()" class="hidden lg:flex w-9 h-9 rounded-lg border border-gray-200 items-center justify-center text-gray-500 hover:bg-gray-50 hover:border-gray-300 transition-colors flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <div class="min-w-0">
                    <h1 class="text-sm font-semibold text-darken truncate">{{ $school->name ?? 'Your School' }}</h1>
                    <p class="text-xs text-gray-400 truncate">
                        {{ $school->school_number ?? '' }}
                        @if(isset($term) && $term) &middot; {{ $term->name }}, {{ $term->year }} @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center space-x-2 lg:space-x-4 flex-shrink-0">
                @include('partials.branch-switcher')
                @if(isset($school))
                    @php
                        $licenceExpiry = $school->license_expires_at?->copy()->startOfDay();
                        $licenceDaysRemaining = $licenceExpiry ? (int) now()->startOfDay()->diffInDays($licenceExpiry, false) : null;
                        $licenceIsExpired = $school->license_status === 'expired' || ($licenceDaysRemaining !== null && $licenceDaysRemaining < 0);
                        $licenceIsUrgent = ! $licenceIsExpired && $licenceDaysRemaining !== null && $licenceDaysRemaining <= 30;
                    @endphp
                    <a href="{{ route('licence.index') }}" wire:navigate
                       class="hidden sm:inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold {{ $licenceIsExpired ? 'bg-red-100 text-red-700' : ($licenceIsUrgent ? 'bg-amber-100 text-amber-800' : 'bg-yellow-100 text-yellow-800') }}">
                        <span class="h-2 w-2 rounded-full {{ $licenceIsExpired ? 'bg-red-500' : ($licenceIsUrgent ? 'bg-amber-500' : 'bg-yellow-500') }}"></span>
                        {{ ucfirst($school->license_plan) }}:
                        @if($licenceIsExpired)
                            expired {{ $licenceExpiry?->format('d M Y') }}
                        @elseif($licenceDaysRemaining === 0)
                            expires today
                        @elseif($licenceDaysRemaining !== null)
                            {{ number_format($licenceDaysRemaining) }} {{ Str::plural('day', $licenceDaysRemaining) }} remaining
                        @else
                            no expiry date
                        @endif
                    </a>
                @endif

                <!-- Search box -->
                <div class="relative hidden md:block">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <input type="text" placeholder="Search students, classes..." class="w-48 lg:w-64 pl-9 pr-4 py-2 text-sm bg-gray-100 border border-transparent rounded-full focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:bg-white transition-colors">
                </div>

                <!-- Notification bell (hover dropdown) -->
                <div class="group relative" x-data="{ open: '' }">
                    <a href="{{ route('notifications.index') }}" wire:navigate aria-label="Open notification center" aria-describedby="notificationsDropdownPanel" title="Notifications" class="relative flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-amber-50 hover:text-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                        @if(collect($dashboardNotifications ?? [])->isNotEmpty())<span class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-red-500"></span>@endif
                    </a>

                    <div id="notificationsDropdownPanel" role="tooltip" class="invisible absolute right-0 top-full z-[100] w-80 pt-2 opacity-0 transition duration-150 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl">
                            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2">
                                <p class="text-sm font-semibold text-darken">Notifications</p>
                                <a href="{{ route('notifications.index') }}" wire:navigate class="text-xs font-bold text-yellow-700 hover:text-yellow-900">View all</a>
                            </div>
                            <div id="notificationsDropdown" class="max-h-80 min-h-12 divide-y divide-gray-100 overflow-y-auto bg-white">
                                @forelse($dashboardNotifications ?? collect() as $notification)
                                    <a href="{{ route('notifications.index') }}" wire:navigate class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50">
                                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg font-bold {{ $notification->type === 'warning' ? 'bg-amber-100 text-amber-700' : ($notification->type === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700') }}">{{ $notification->type === 'warning' ? '!' : ($notification->type === 'success' ? '✓' : 'i') }}</span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-semibold text-slate-800">{{ $notification->title }}</span>
                                            <span class="mt-0.5 block text-xs leading-5 text-slate-500">{{ Str::limit($notification->message, 100) }}</span>
                                            <span class="mt-1 block text-[11px] text-slate-400">{{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}</span>
                                        </span>
                                    </a>
                                @empty
                                    <div class="px-4 py-8 text-center text-sm text-slate-400">No notifications yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User dropdown (hover) -->
                <div class="relative group">
                    <button class="flex items-center space-x-2 pl-2 pr-1 py-1 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-sm font-medium text-gray-600 overflow-hidden">
                            @if(auth()->user()->avatarUrl())
                                <img src="{{ auth()->user()->avatarUrl() }}" class="w-full h-full object-cover" alt="{{ auth()->user()->name }}">
                            @else
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            @endif
                        </div>
                        <svg class="w-4 h-4 text-gray-400 hidden sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                    </button>

                    <div class="absolute right-0 mt-1 w-56 bg-white rounded-xl shadow-lg border border-gray-100 py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-150 z-30">
                        <div class="px-4 py-2 border-b border-gray-100">
                            <p class="text-sm font-medium text-darken truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                        </div>
                        <button type="button" onclick="Livewire.dispatch('open-profile-modal')" class="w-full flex items-center space-x-3 px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50 text-left">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            <span>Edit profile</span>
                        </button>
                        <button type="button" onclick="Livewire.dispatch('open-profile-modal')" class="w-full flex items-center space-x-3 px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50 text-left">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            <span>Change password</span>
                        </button>
                        <a href="#" class="flex items-center space-x-3 px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            <span>Settings</span>
                        </a>
                        <div class="border-t border-gray-100 mt-1 pt-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center space-x-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 text-left">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                    <span>Log out</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="p-6 lg:p-10">

            <!-- Greeting card -->
            <div class="rounded-2xl p-6 lg:p-8 mb-8 relative overflow-hidden shadow-lg ring-2 ring-yellow-400/20" style="background: linear-gradient(135deg, #252641 0%, #3a3d6b 100%);">
                <div class="relative z-10">
                    <h2 class="text-amber-300 text-2xl font-semibold mb-1">Welcome back, {{ explode(' ', auth()->user()->name)[0] }} 👋</h2>
                    <p class="text-gray-300 text-sm">Here's what's happening at {{ $school->name ?? 'your school' }} today.</p>
                    <p class="text-yellow-400 text-xs font-medium mt-3" id="greetingDateTime">Loading...</p>
                </div>
                <div class="absolute -right-6 -bottom-10 w-40 h-40 bg-yellow-400/10 rounded-full"></div>
                <div class="absolute right-16 -top-10 w-24 h-24 bg-yellow-400/10 rounded-full"></div>
            </div>

            <!-- Stat cards with monthly progress -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    @php
        $stats = [
            ['label' => 'Students', 'value' => $studentCount, 'added' => $studentsAddedThisMonth, 'color' => 'amber', 'icon' => 'heroicon-o-academic-cap'],
            ['label' => 'Classes', 'value' => $classCount, 'added' => $classesAddedThisMonth, 'color' => 'blue', 'icon' => 'heroicon-o-rectangle-stack'],
            ['label' => 'Streams', 'value' => $streamCount, 'added' => $streamsAddedThisMonth, 'color' => 'purple', 'icon' => 'heroicon-o-queue-list'],
            ['label' => 'Staff', 'value' => $staffCount, 'added' => $staffAddedThisMonth, 'color' => 'green', 'icon' => 'heroicon-o-user-group'],
        ];
    @endphp
    @foreach($stats as $stat)
        <div class="bg-white rounded-2xl shadow-sm ring-2 ring-[#252641]/10 p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
            <div class="w-10 h-10 rounded-xl bg-{{ $stat['color'] }}-50 text-{{ $stat['color'] }}-600 flex items-center justify-center mb-4">
                <!-- Cleaner dynamic icon picker -->
                <x-dynamic-component :component="$stat['icon']" class="w-5 h-5" stroke-width="1.8" />
            </div>
            <p class="text-2xl font-bold text-darken">{{ $stat['value'] }}</p>
            <p class="text-gray-500 text-sm mt-1">{{ $stat['label'] }}</p>
            @if($stat['added'] > 0)
                <p class="text-xs text-green-600 mt-2 flex items-center space-x-1">
                    <span>↑</span><span>{{ $stat['added'] }} added this month</span>
                </p>
            @else
                <p class="text-xs text-gray-300 mt-2">No new {{ strtolower($stat['label']) }} this month</p>
            @endif
        </div>
    @endforeach
</div>

<div class="mb-8 grid gap-4 sm:grid-cols-3"><div class="rounded-2xl bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase text-slate-400">Attendance today</p><p class="mt-2 text-2xl font-bold text-slate-900">{{ $attendanceRateToday }}%</p><p class="mt-1 text-sm text-slate-500">{{ $presentToday }} present / {{ $activeLearners }} active learners</p></div><div class="rounded-2xl bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase text-slate-400">Absent today</p><p class="mt-2 text-2xl font-bold text-rose-600">{{ $absentToday }}</p><a href="{{ route('attendance.index') }}" class="mt-2 inline-block text-sm font-bold text-yellow-700">Mark attendance →</a></div><div class="rounded-2xl bg-slate-800 p-5 text-white"><p class="text-xs font-bold uppercase text-slate-400">Current term</p><p class="mt-2 text-lg font-bold">{{ $term ? $term->name.', '.$term->year : 'No open term' }}</p><p class="mt-1 text-sm text-slate-300">Attendance and finance are shown in this context.</p></div></div>
<!-- Live financial position for the current term -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    @php
        $sampleStats = [
            ['label' => 'Expected this term', 'value' => 'UGX '.number_format($totalExpected), 'color' => 'blue', 'icon' => 'heroicon-o-calculator', 'note' => 'Fees plus arrears'],
            ['label' => 'Fees collected', 'value' => 'UGX '.number_format($feesPaid), 'color' => 'emerald', 'icon' => 'heroicon-o-banknotes', 'note' => 'Posted to cash pool'],
            ['label' => 'Pending fees', 'value' => 'UGX '.number_format($pendingFees), 'color' => 'amber', 'icon' => 'heroicon-o-clock', 'note' => 'Arrears: UGX '.number_format($arrearsAmount)],
            ['label' => 'Pool balance', 'value' => 'UGX '.number_format($poolBalance), 'color' => 'indigo', 'icon' => 'heroicon-o-building-library', 'note' => 'Expenses: UGX '.number_format($termExpenses)],
        ];
    @endphp
    @foreach($sampleStats as $stat)
        <div class="bg-white rounded-2xl shadow-sm ring-2 ring-[#252641]/10 p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-{{ $stat['color'] }}-50 text-{{ $stat['color'] }}-600 flex items-center justify-center">
                    <!-- Cleaner dynamic icon picker -->
                    <x-dynamic-component :component="$stat['icon']" class="w-5 h-5" stroke-width="1.8" />
                </div>
                <span class="text-[10px] uppercase tracking-wide text-gray-300 font-medium mt-1">Live</span>
            </div>
            <p class="text-2xl font-bold text-darken">{{ $stat['value'] }}</p>
            <p class="text-gray-500 text-sm mt-1">{{ $stat['label'] }}</p>
            <p class="text-xs text-gray-400 mt-2">{{ $stat['note'] }}</p>
        </div>
    @endforeach
</div>
     <!-- Row 2: Debtors Spotlight (Standalone Below) -->

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 ring-2 ring-yellow-400/30 p-5 mb-8 hover:shadow-md hover:ring-yellow-400/50 transition-all duration-300">
    <div class="flex items-center justify-between mb-6">
        <div class="space-y-1">
            <h3 class="font-bold text-gray-900 text-base tracking-tight flex items-center gap-2">
                <span>Debtors Spotlight</span>
                <span class="flex h-2 w-2 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-yellow-500"></span>
                </span>
            </h3>
            <p class="text-xs text-gray-400 font-medium">Learners with outstanding balances in the active term</p>
        </div>
        <a href="{{ request()->fullUrl() }}" class="text-xs font-bold text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 px-3 py-2 rounded-xl transition flex items-center gap-1.5 shadow-sm active:scale-95">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.253 8H18" /></svg>
            Refresh
        </a>
    </div>

    <!-- Dynamic Target Container for JavaScript Arrears Grid -->
    <div id="debtorsSpotlightGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($dashboardDebtors as $debtor)
            <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-100 bg-gray-50/40 p-4">
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-gray-900">{{ $debtor['name'] }}</p>
                    <p class="text-xs text-gray-400">{{ $debtor['class'] }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <span class="rounded-lg bg-[#252641] px-2.5 py-1 text-xs font-bold text-[#facc15]">{{ $currencySymbol }} {{ number_format($debtor['balance']) }}</span>
                    <a title="View learner profile" href="{{ route('students.index', ['student' => $debtor['id']]) }}" class="rounded-lg border px-2 py-1 text-xs">Profile</a>
                    <a title="Pay this learner's fees" href="{{ route('fee-payments.index', ['student' => $debtor['id']]) }}" class="rounded-lg border px-2 py-1 text-xs">Pay</a>
                </div>
            </div>
        @empty
            <p class="col-span-full p-5 text-sm text-gray-500">No active-term debtors.</p>
        @endforelse

        @if(false)
        <!-- Inside here, your dynamic script will render student layout components like this: -->
         
        <!-- Record 1 -->
<div class="p-4 rounded-xl border border-gray-100 ring-2 ring-yellow-400/20 bg-gray-50/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-gray-50 hover:ring-yellow-400/40 transition-all duration-200 shadow-sm">
    <div class="flex items-center justify-between sm:justify-start gap-4 flex-1">
        <div>
            <p class="font-bold text-gray-900 text-sm">John Doe</p>
            <p class="text-xs text-gray-400">Primary 6 Red</p>
        </div>
        <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-[#252641] text-[#facc15] shadow-sm">
            $450.00
        </span>
    </div>
    <!-- Actions with Instant Tooltips -->
    <div class="flex items-center gap-1.5 self-end sm:self-center border-t sm:border-t-0 pt-2 sm:pt-0 border-gray-100 w-full sm:w-auto justify-end">
        
        <!-- View Account -->
        <div class="relative group">
            <button class="p-2 text-gray-500 hover:text-gray-900 hover:bg-white rounded-lg border border-transparent hover:border-gray-200 transition active:scale-95 shadow-sm hover:shadow-xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
            </button>
            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block bg-gray-900 text-white text-[10px] font-medium px-2 py-1 rounded shadow-md whitespace-nowrap pointer-events-none z-10">
                View Account
            </span>
        </div>

        <!-- Send Email Warning -->
        <div class="relative group">
            <button class="p-2 text-gray-500 hover:text-amber-600 hover:bg-white rounded-lg border border-transparent hover:border-gray-200 transition active:scale-95 shadow-sm hover:shadow-xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </button>
            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block bg-gray-900 text-white text-[10px] font-medium px-2 py-1 rounded shadow-md whitespace-nowrap pointer-events-none z-10">
                Send Email Warning
            </span>
        </div>

        <!-- Visit Student Profile -->
        <div class="relative group">
            <button class="p-2 text-gray-500 hover:text-[#252641] hover:bg-white rounded-lg border border-transparent hover:border-gray-200 transition active:scale-95 shadow-sm hover:shadow-xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
            </button>
            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block bg-gray-900 text-white text-[10px] font-medium px-2 py-1 rounded shadow-md whitespace-nowrap pointer-events-none z-10">
                Visit Student Profile
            </span>
        </div>

    </div>
</div>

<!-- Record 2 -->
<div class="p-4 rounded-xl border border-gray-100 ring-2 ring-yellow-400/20 bg-gray-50/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-gray-50 hover:ring-yellow-400/40 transition-all duration-200 shadow-sm">
    <div class="flex items-center justify-between sm:justify-start gap-4 flex-1">
        <div>
            <p class="font-bold text-gray-900 text-sm">Jane Smith</p>
            <p class="text-xs text-gray-400">Primary 5 Blue</p>
        </div>
        <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-[#252641] text-[#facc15] shadow-sm">
            $320.00
        </span>
    </div>
    <div class="flex items-center gap-1.5 self-end sm:self-center border-t sm:border-t-0 pt-2 sm:pt-0 border-gray-100 w-full sm:w-auto justify-end">
        <div class="relative group">
            <button class="p-2 text-gray-500 hover:text-gray-900 hover:bg-white rounded-lg border border-transparent hover:border-gray-200 transition active:scale-95 shadow-sm hover:shadow-xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
            </button>
            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block bg-gray-900 text-white text-[10px] font-medium px-2 py-1 rounded shadow-md whitespace-nowrap pointer-events-none z-10">
                View Account
            </span>
        </div>
        <div class="relative group">
            <button class="p-2 text-gray-500 hover:text-amber-600 hover:bg-white rounded-lg border border-transparent hover:border-gray-200 transition active:scale-95 shadow-sm hover:shadow-xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </button>
            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block bg-gray-900 text-white text-[10px] font-medium px-2 py-1 rounded shadow-md whitespace-nowrap pointer-events-none z-10">
                Send Email Warning
            </span>
        </div>
        <div class="relative group">
            <button class="p-2 text-gray-500 hover:text-[#252641] hover:bg-white rounded-lg border border-transparent hover:border-gray-200 transition active:scale-95 shadow-sm hover:shadow-xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
            </button>
            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block bg-gray-900 text-white text-[10px] font-medium px-2 py-1 rounded shadow-md whitespace-nowrap pointer-events-none z-10">
                Visit Student Profile
            </span>
        </div>
    </div>
</div>

<!-- Record 3 -->
<div class="p-4 rounded-xl border border-gray-100 ring-2 ring-yellow-400/20 bg-gray-50/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-gray-50 hover:ring-yellow-400/40 transition-all duration-200 shadow-sm">
    <div class="flex items-center justify-between sm:justify-start gap-4 flex-1">
        <div>
            <p class="font-bold text-gray-900 text-sm">Alex Johnson</p>
            <p class="text-xs text-gray-400">Primary 6 Green</p>
        </div>
        <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-[#252641] text-[#facc15] shadow-sm">
            $600.00
        </span>
    </div>
    <div class="flex items-center gap-1.5 self-end sm:self-center border-t sm:border-t-0 pt-2 sm:pt-0 border-gray-100 w-full sm:w-auto justify-end">
        <div class="relative group">
            <button class="p-2 text-gray-500 hover:text-gray-900 hover:bg-white rounded-lg border border-transparent hover:border-gray-200 transition active:scale-95 shadow-sm hover:shadow-xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
            </button>
            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block bg-gray-900 text-white text-[10px] font-medium px-2 py-1 rounded shadow-md whitespace-nowrap pointer-events-none z-10">
                View Account
            </span>
        </div>
        <div class="relative group">
            <button class="p-2 text-gray-500 hover:text-amber-600 hover:bg-white rounded-lg border border-transparent hover:border-gray-200 transition active:scale-95 shadow-sm hover:shadow-xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </button>
            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block bg-gray-900 text-white text-[10px] font-medium px-2 py-1 rounded shadow-md whitespace-nowrap pointer-events-none z-10">
                Send Email Warning
            </span>
        </div>
        <div class="relative group">
            <button class="p-2 text-gray-500 hover:text-[#252641] hover:bg-white rounded-lg border border-transparent hover:border-gray-200 transition active:scale-95 shadow-sm hover:shadow-xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
            </button>
            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block bg-gray-900 text-white text-[10px] font-medium px-2 py-1 rounded shadow-md whitespace-nowrap pointer-events-none z-10">
                Visit Student Profile
            </span>
        </div>
    </div>
</div>

<!-- Record 4 -->
<div class="p-4 rounded-xl border border-gray-100 ring-2 ring-yellow-400/20 bg-gray-50/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-gray-50 hover:ring-yellow-400/40 transition-all duration-200 shadow-sm">
    <div class="flex items-center justify-between sm:justify-start gap-4 flex-1">
        <div>
            <p class="font-bold text-gray-900 text-sm">Mary Williams</p>
            <p class="text-xs text-gray-400">Primary 4 Yellow</p>
        </div>
        <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-[#252641] text-[#facc15] shadow-sm">
            $180.00
        </span>
    </div>
    <div class="flex items-center gap-1.5 self-end sm:self-center border-t sm:border-t-0 pt-2 sm:pt-0 border-gray-100 w-full sm:w-auto justify-end">
        <div class="relative group">
            <button class="p-2 text-gray-500 hover:text-gray-900 hover:bg-white rounded-lg border border-transparent hover:border-gray-200 transition active:scale-95 shadow-sm hover:shadow-xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
            </button>
            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block bg-gray-900 text-white text-[10px] font-medium px-2 py-1 rounded shadow-md whitespace-nowrap pointer-events-none z-10">
                View Account
            </span>
        </div>
        <div class="relative group">
            <button class="p-2 text-gray-500 hover:text-amber-600 hover:bg-white rounded-lg border border-transparent hover:border-gray-200 transition active:scale-95 shadow-sm hover:shadow-xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </button>
            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block bg-gray-900 text-white text-[10px] font-medium px-2 py-1 rounded shadow-md whitespace-nowrap pointer-events-none z-10">
                Send Email Warning
            </span>
        </div>
        <div class="relative group">
            <button class="p-2 text-gray-500 hover:text-[#252641] hover:bg-white rounded-lg border border-transparent hover:border-gray-200 transition active:scale-95 shadow-sm hover:shadow-xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
            </button>
            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block bg-gray-900 text-white text-[10px] font-medium px-2 py-1 rounded shadow-md whitespace-nowrap pointer-events-none z-10">
                Visit Student Profile
            </span>
        </div>
    </div>
</div>

<!-- Record 5 -->
<div class="p-4 rounded-xl border border-gray-100 ring-2 ring-yellow-400/20 bg-gray-50/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-gray-50 hover:ring-yellow-400/40 transition-all duration-200 shadow-sm">
    <div class="flex items-center justify-between sm:justify-start gap-4 flex-1">
        <div>
            <p class="font-bold text-gray-900 text-sm">David Brown</p>
            <p class="text-xs text-gray-400">Primary 5 Red</p>
        </div>
        <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-[#252641] text-[#facc15] shadow-sm">
            $750.00
        </span>
    </div>
    <div class="flex items-center gap-1.5 self-end sm:self-center border-t sm:border-t-0 pt-2 sm:pt-0 border-gray-100 w-full sm:w-auto justify-end">
        <div class="relative group">
            <button class="p-2 text-gray-500 hover:text-gray-900 hover:bg-white rounded-lg border border-transparent hover:border-gray-200 transition active:scale-95 shadow-sm hover:shadow-xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
            </button>
            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block bg-gray-900 text-white text-[10px] font-medium px-2 py-1 rounded shadow-md whitespace-nowrap pointer-events-none z-10">
                View Account
            </span>
        </div>
        <div class="relative group">
            <button class="p-2 text-gray-500 hover:text-amber-600 hover:bg-white rounded-lg border border-transparent hover:border-gray-200 transition active:scale-95 shadow-sm hover:shadow-xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </button>
            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block bg-gray-900 text-white text-[10px] font-medium px-2 py-1 rounded shadow-md whitespace-nowrap pointer-events-none z-10">
                Send Email Warning
            </span>
        </div>
        <div class="relative group">
            <button class="p-2 text-gray-500 hover:text-[#252641] hover:bg-white rounded-lg border border-transparent hover:border-gray-200 transition active:scale-95 shadow-sm hover:shadow-xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
            </button>
            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block bg-gray-900 text-white text-[10px] font-medium px-2 py-1 rounded shadow-md whitespace-nowrap pointer-events-none z-10">
                Visit Student Profile
            </span>
        </div>
    </div>
</div>
        @endif
        
    </div>
</div>
       
<!-- Attendance & Demographics -->
<form method='GET' action='/dashboard' class='mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm'>
    <label for='dashboardTermFilter' class='text-xs font-bold uppercase text-gray-500'>Dashboard term</label>
    <select id='dashboardTermFilter' name='dashboard_term' class='rounded-xl border-gray-200 text-sm'>
        @foreach($school->terms->sortByDesc('year') as $availableTerm)<option value='{{ $availableTerm->id }}' @selected($term?->id === $availableTerm->id)>{{ $availableTerm->name }}, {{ $availableTerm->year }}</option>@endforeach
    </select>
    @foreach(['attendance_date','attendance_class','revenue_type','payment_year','performance_class'] as $filter) @if(request()->filled($filter))<input type='hidden' name='{{ $filter }}' value='{{ request($filter) }}'>@endif @endforeach
    <button class='rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white'>Apply term</button>
</form>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    
    <!-- Attendance Card (Takes 2 columns on desktop) -->
    <div class="md:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 ring-2 ring-yellow-400/30 p-5 hover:shadow-md hover:ring-yellow-400/50 transition-all duration-300">
        
        <!-- Header & Controls -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div class="space-y-1">
                <h3 class="font-bold text-gray-900 text-base tracking-tight">Attendance this week</h3>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium bg-amber-50 text-amber-800 border border-amber-100/70">
                    Sample data — connects when Attendance module is live
                </span>
            </div>
            
            <!-- Filter Controls -->
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select id="attendanceClassFilter" 
                    class="text-xs font-semibold border border-gray-200 rounded-xl px-3 py-2 text-gray-700 bg-gray-50/50 hover:bg-white hover:border-gray-300 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 transition-all flex-1 sm:flex-none min-w-[130px] shadow-sm">
                    <option value="all">All classes</option>
                    @foreach($school->classes ?? [] as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
                
                <input type="date" id="attendanceDateFilter" 
                    class="text-xs font-semibold border border-gray-200 rounded-xl px-3 py-2 text-gray-700 bg-gray-50/50 hover:bg-white hover:border-gray-300 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 transition-all flex-1 sm:flex-none shadow-sm">
            </div>
        </div>
        
        <!-- Chart Canvas Area -->
        <div class="relative w-full h-64 sm:h-72 bg-gray-50/30 rounded-xl border border-gray-100/50 p-2">
            <canvas id="attendanceChart"></canvas>
        </div>
    </div>
    
    <!-- Gender Breakdown Card (Takes 1 column on desktop) -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 ring-2 ring-yellow-400/30 p-5 hover:shadow-md hover:ring-yellow-400/50 transition-all duration-300 flex flex-col justify-between">
        
        <!-- Header -->
        <div class="space-y-1 mb-6">
            <h3 class="font-bold text-gray-900 text-base tracking-tight">School Demographics</h3>
            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium bg-gray-100 text-gray-600 border border-gray-200">
                Gender Ratio
            </span>
        </div>
        
        <!-- Pie Chart Canvas Area -->
        <div class="relative w-full h-64 sm:h-72 bg-gray-50/30 rounded-xl border border-gray-100/50 p-4 flex items-center justify-center">
            <div class="relative w-full h-full max-h-[200px] flex items-center justify-center">
                <canvas id="genderPieChart"></canvas>
            </div>
        </div>
    </div>
    
</div>
            <!-- Revenue -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 ring-2 ring-yellow-400/30 p-5 mb-8 hover:shadow-md hover:ring-yellow-400/50 transition-all duration-300">
    
    <!-- Header & Controls -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div class="space-y-1">
            <h3 class="font-bold text-gray-900 text-base tracking-tight">Income &amp; expenditure</h3>
            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium bg-amber-50 text-amber-800 border border-amber-100/70">
                Live cash-pool activity for the active term
            </span>
        </div>
        
        <!-- Filter Controls -->
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <select id="revenueTypeFilter" 
                class="text-xs font-semibold border border-gray-200 rounded-xl px-3 py-2 text-gray-700 bg-gray-50/50 hover:bg-white hover:border-gray-300 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 transition-all flex-1 sm:flex-none min-w-[150px] shadow-sm">
                <option value="both">Income &amp; Expenditure</option>
                <option value="income">Income only</option>
                <option value="expenditure">Expenditure only</option>
            </select>
            
        </div>
    </div>
    
    <!-- Chart Canvas Area -->
    <div class="relative w-full h-64 sm:h-72 bg-gray-50/30 rounded-xl border border-gray-100/50 p-2">
        <canvas id="revenueChart"></canvas>
    </div>
</div>
<!-- Line Graphs Row (Side-by-Side on Desktop) -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

    <!-- Card 1: Student Payment Trend -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 ring-2 ring-yellow-400/30 p-5 hover:shadow-md hover:ring-yellow-400/50 transition-all duration-300">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div class="space-y-1">
                <h3 class="font-bold text-gray-900 text-base tracking-tight">Payment Trends</h3>
                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Financial Insights</span>
            </div>
            
            <!-- Filters -->
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select id="paymentYearFilter" 
                    class="text-xs font-semibold border border-gray-200 rounded-xl px-3 py-2 text-gray-700 bg-gray-50/50 hover:bg-white hover:border-gray-300 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 transition-all flex-1 sm:flex-none shadow-sm">
                    @foreach($paymentYears as $year)
                        <option value="{{ $year }}">{{ $year }} Academic Year</option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <div class="relative w-full h-64 sm:h-72 bg-gray-50/30 rounded-xl border border-gray-100/50 p-2">
            <canvas id="paymentTrendChart"></canvas>
        </div>
    </div>

    <!-- Card 2: Class Performance Analytics -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 ring-2 ring-yellow-400/30 p-5 hover:shadow-md hover:ring-yellow-400/50 transition-all duration-300">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div class="space-y-1">
                <h3 class="font-bold text-gray-900 text-base tracking-tight">Academic Performance</h3>
                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Grade Averages</span>
            </div>
            
            <!-- Filters -->
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select id="performanceClassFilter" 
                    class="text-xs font-semibold border border-gray-200 rounded-xl px-3 py-2 text-gray-700 bg-gray-50/50 hover:bg-white hover:border-gray-300 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 transition-all flex-1 sm:flex-none min-w-[120px] shadow-sm">
                    <option value="all">All Classes</option>
                    @foreach($school->classes ?? [] as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <div class="relative w-full h-64 sm:h-72 bg-gray-50/30 rounded-xl border border-gray-100/50 p-2">
            <canvas id="classPerformanceChart"></canvas>
        </div>
    </div>
</div>


            <div class="grid lg:grid-cols-3 gap-6 mb-8">

                <!-- Calendar -->
                <!-- Calendar Widget -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 ring-2 ring-yellow-400/30 p-5 hover:shadow-md hover:ring-yellow-400/50 transition-all duration-300">
    
    <!-- Inject styling directly for dynamically generated items -->
    <style>
        /* Styles standard day items generated by your script */
        #calendarGrid > * {
            aspect-ratio: 1 / 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.75rem; /* rounded-xl */
            transition: all 0.2s;
            cursor: pointer;
        }
        #calendarGrid > *:hover {
            background-color: #f9fafb; /* bg-gray-50 */
        }
        /* Targets active, today, or selected classes your JS adds (e.g., .active, .today, .selected) */
        #calendarGrid > .today,
        #calendarGrid > .selected,
        #calendarGrid > .active {
            font-weight: 700;
            color: #111827; /* text-gray-900 */
            background-color: rgba(254, 243, 199, 0.4); /* bg-yellow-50/40 */
            box-shadow: 0 0 0 2px #facc15; /* ring-2 ring-yellow-400 */
        }
    </style>

    <!-- Header Controls -->
    <div class="flex items-center justify-between mb-5">
        <h3 class="font-bold text-gray-900 text-base tracking-tight" id="calendarMonthLabel"></h3>
        <div class="flex items-center space-x-1 bg-gray-50 p-0.5 rounded-xl border border-gray-100">
            <button id="calPrev" class="w-8 h-8 rounded-lg hover:bg-white hover:shadow-sm flex items-center justify-center text-gray-500 hover:text-gray-800 transition active:scale-95">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            </button>
            <button id="calNext" class="w-8 h-8 rounded-lg hover:bg-white hover:shadow-sm flex items-center justify-center text-gray-500 hover:text-gray-800 transition active:scale-95">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
            </button>
        </div>
    </div>

    <!-- Weekday Labels -->
    <div class="grid grid-cols-7 gap-1 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">
        <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
    </div>

    <!-- Calendar Day Grid -->
    <div id="calendarGrid" class="grid grid-cols-7 gap-1 text-center text-sm font-medium text-gray-700"></div>

    <!-- Upcoming Events Panel -->
    <div class="mt-5 border-t border-gray-100 pt-4">
        <div class="flex items-center space-x-1.5 mb-3">
            <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full animate-pulse"></span>
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Upcoming Agenda</p>
        </div>
        
        <!-- Target container for Javascript rendered events list -->
        <div id="calendarEventsList" class="space-y-2 text-sm text-gray-600"></div>
    </div>

</div>

                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 ring-2 ring-yellow-400/30 p-5 overflow-x-auto hover:shadow-md hover:ring-yellow-400/50 transition-all duration-300">
    
    <!-- Inject style framework directly for dynamic JavaScript row data -->
    <style>
        #timetableBody tr {
            border-bottom: 1px solid #f3f4f6; /* border-gray-100 */
            transition: background-color 0.2s;
        }
        #timetableBody tr:hover {
            background-color: #f9fafb; /* bg-gray-50 */
        }
        #timetableBody td {
            padding: 0.875rem 0.5rem; /* py-3.5 px-2 */
            vertical-align: middle;
        }
        /* Style for standard subjects cards your JS might render */
        #timetableBody .subject-slot {
            padding: 0.375rem 0.625rem;
            border-radius: 0.5rem;
            font-weight: 500;
            display: inline-block;
            background-color: #f3f4f6;
            color: #374151;
        }
        /* Highlight specific active items/slots if needed */
        #timetableBody .active-slot {
            background-color: rgba(254, 243, 199, 0.5); /* yellow-50/50 */
            color: #713f12; /* text-yellow-900 */
            border: 1px solid #fde047; /* border-yellow-300 */
        }
    </style>

    <!-- Header Block -->
    <div class="flex items-center justify-between mb-5">
        <div class="flex items-center space-x-2">
            <h3 class="font-bold text-gray-900 text-base tracking-tight">Timetable</h3>
            <form method="GET" action="{{ route('dashboard') }}">
                <select name="timetable_class" onchange="this.form.submit()" class="rounded-lg border border-gray-200 bg-gray-50 px-2 py-1 text-[11px] font-semibold text-gray-700">
                    @forelse($dashboardTimetableClasses as $class)
                        <option value="{{ $class->id }}" @selected($class->id===$dashboardTimetableClassId)>{{ $class->name }}</option>
                    @empty
                        <option value="">No classes</option>
                    @endforelse
                </select>
            </form>
        </div>
        <a href="{{ route('timetable.index') }}" class="text-[11px] font-semibold uppercase tracking-wider text-yellow-700 hover:underline">Manage timetable</a>
    </div>

    <!-- Table Element -->
    <table class="w-full text-xs min-w-[550px] border-collapse">
        <thead>
            <tr class="text-gray-400 border-b border-gray-100 font-semibold uppercase tracking-wider text-[10px] text-center">
                <th class="text-left py-3 pr-4 font-bold text-gray-500 normal-case text-xs w-[80px]">Time</th>
                <th class="py-3 px-2">Mon</th>
                <th class="py-3 px-2">Tue</th>
                <th class="py-3 px-2">Wed</th>
                <th class="py-3 px-2">Thu</th>
                <th class="py-3 px-2">Fri</th>
            </tr>
        </thead>
        <!-- Dynamic target for table row items generated by JavaScript -->
        <tbody id="timetableBody" class="text-gray-700 text-center font-medium"></tbody>
    </table>
</div>
            </div>

            <!-- Classes overview -->
           <!-- Classes overview -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 ring-2 ring-yellow-400/30 p-5 mb-8 hover:shadow-md hover:ring-yellow-400/50 transition-all duration-300">
    
    <!-- Header Controls -->
    <div class="flex items-center justify-between mb-5">
        <h3 class="font-bold text-gray-900 text-base tracking-tight">Your classes</h3>
        <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Overview</span>
    </div>

    @if($school && $school->classes->count())
        <div class="space-y-2.5">
            @foreach($school->classes as $class)
                <div class="flex items-center justify-between p-3.5 bg-gray-50/50 rounded-xl border border-gray-100/70 hover:bg-gray-50 transition-colors duration-200">
                    <div class="space-y-0.5">
                        <p class="font-bold text-gray-900 text-sm tracking-tight">{{ $class->name }}</p>
                        <p class="text-xs text-gray-500 font-medium">
                            {{ $class->streams->pluck('name')->join(', ') ?: 'No streams yet' }}
                        </p>
                    </div>
                    <div class="flex items-center space-x-2 bg-white px-3 py-1.5 rounded-lg border border-gray-100 shadow-sm">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span class="text-xs font-semibold text-gray-700 whitespace-nowrap">
                            {{ $class->students->count() }} {{ Str::plural('student', $class->students->count()) }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-6 text-center bg-gray-50/50 border border-dashed border-gray-200 rounded-xl">
            <svg class="w-8 h-8 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
            </svg>
            <p class="text-gray-400 text-sm font-medium">No classes set up yet.</p>
        </div>
    @endif
</div>
<div class="mb-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @php $collectionRate = $totalExpected > 0 ? min(100, round(($feesPaid / $totalExpected) * 100, 1)) : 0; @endphp
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div><p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $term ? $term->name.', '.$term->year : 'No open term' }}</p><h3 class="mt-1 text-lg font-bold text-slate-900">Term financial position</h3><p class="mt-1 text-sm text-slate-500">Fee demand is calculated only from active learners. Inactive learners are excluded.</p></div>
        <a href="{{ route('fee-payments.index') }}" class="rounded-xl bg-yellow-400 px-4 py-2 text-sm font-bold text-slate-900 hover:bg-yellow-300">Record payment</a>
    </div>
    <div class="mt-6 grid gap-5 lg:grid-cols-3">
        <div class="lg:col-span-2"><div class="flex items-center justify-between text-sm"><span class="font-semibold text-slate-700">Collection progress</span><span class="font-bold text-emerald-700">{{ $collectionRate }}%</span></div><div class="mt-2 h-3 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $collectionRate }}%"></div></div><div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-500"><span>Demand: <strong class="text-slate-800">UGX {{ number_format($totalExpected) }}</strong></span><span>Collected: <strong class="text-emerald-700">UGX {{ number_format($feesPaid) }}</strong></span><span>Outstanding: <strong class="text-rose-600">UGX {{ number_format($pendingFees) }}</strong></span></div></div>
        <div class="rounded-xl bg-slate-900 p-4 text-white"><p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Available cash pool</p><p class="mt-2 text-2xl font-bold">UGX {{ number_format($poolBalance) }}</p><p class="mt-2 text-xs text-slate-300">Term spending: UGX {{ number_format($termExpenses) }}</p></div>
    </div>
</div>

            <div class="grid lg:grid-cols-2 gap-6">
    
    <!-- Inject style framework for dynamic items in both containers -->
    <style>
        #remindersList > *, #notificationsList > * {
            display: flex;
            align-items: flex-start;
            padding: 0.75rem 0.875rem;
            border-radius: 0.75rem; /* rounded-xl */
            font-size: 0.875rem; /* text-sm */
            transition: all 0.2s;
            border: 1px solid #f3f4f6; /* border-gray-100 */
            background-color: rgba(249, 250, 251, 0.5); /* bg-gray-50/50 */
            margin-bottom: 0.625rem;
        }
        #remindersList > *:hover, #notificationsList > *:hover {
            background-color: #f9fafb; /* bg-gray-50 */
            border-color: #e5e7eb; /* border-gray-200 */
        }
    </style>
    

    <!-- Reminders Panel -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 border-l-4 border-l-yellow-400 ring-2 ring-yellow-400/20 p-5 hover:shadow-md hover:ring-yellow-400/40 transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-900 text-base tracking-tight">Reminders</h3>
            <span class="w-2 h-2 rounded-full bg-yellow-400 shadow-[0_0_8px_rgba(250,204,21,0.6)]"></span>
        </div>
        
        <!-- Target container for Javascript rendered items -->
        <div id="remindersList" class="text-gray-700 font-medium"></div>
    </div>

    <!-- Notifications Panel -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 ring-2 ring-yellow-400/20 p-5 hover:shadow-md hover:ring-yellow-400/40 transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-900 text-base tracking-tight ">Notifications</h3>
            <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Recent</span>
        </div>
        
        <!-- Target container for Javascript rendered items -->
        <div id="notificationsList" class="text-gray-700 font-medium "></div>
    </div>

</div>

        </main>
    </div>
    

    <script>
        // Shared palette, available to every block below.
        const colors = { yellow: '#eab308', navy: '#252641', teal: '#0d9488', red: '#ef4444', gray: '#e5e7eb' };
        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>'"]/g, character => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
            })[character]);
        }
        Chart.register({ id: 'emptyState', afterDraw(chart) {
            const hasData = chart.data.datasets.some(dataset => !dataset.hidden && (dataset.data ?? []).some(value => Number(value) !== 0));
            if (hasData) return;
            const { ctx, chartArea } = chart; if (!chartArea) return; ctx.save(); ctx.fillStyle = '#64748b'; ctx.textAlign = 'center'; ctx.font = '600 13px sans-serif'; ctx.fillText('No records for this selection', (chartArea.left + chartArea.right) / 2, (chartArea.top + chartArea.bottom) / 2); ctx.restore();
        }});
    </script>

    <script>
      try {
        // ---- Attendance chart (sample, filterable) ----
        const attendanceDatasets = @json($attendanceSeries);
        const attendanceCtx = document.getElementById('attendanceChart');
        document.getElementById('attendanceDateFilter').value = @json($attendanceDate);
        document.getElementById('attendanceClassFilter').value = @json((string) request('attendance_class', 'all'));
        const initialAttendanceSeries = attendanceDatasets[document.getElementById('attendanceClassFilter').value] ?? attendanceDatasets.all;
        let attendanceChart = new Chart(attendanceCtx, {
            type: 'bar',
            data: {
                labels: @json($attendanceLabels),
                datasets: [
                    { label: 'Present', data: initialAttendanceSeries.present, backgroundColor: colors.yellow, borderRadius: 5, maxBarThickness: 32 },
                    { label: 'Absent', data: initialAttendanceSeries.absent, backgroundColor: colors.navy, borderRadius: 5, maxBarThickness: 32 },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
                scales: { y: { beginAtZero: true } }
            }
        });
        document.getElementById('attendanceClassFilter').addEventListener('change', function () {
            // Sample re-render — swaps to slightly different mock numbers per "class" selected.
            const key = this.value;
            const url = new URL(window.location);
            url.searchParams.set('attendance_class', key);
            history.replaceState({}, '', url);
            attendanceChart.data.datasets[0].data = attendanceDatasets[key].present;
            attendanceChart.data.datasets[1].data = attendanceDatasets[key].absent;
            attendanceChart.update();
        });
        document.getElementById('attendanceDateFilter').addEventListener('change', function () {
            const url = new URL(window.location);
            url.searchParams.set('attendance_date', this.value);
            window.location.assign(url);
            // Placeholder until the Attendance module exists — filtering by a single day
            // will show that day's actual present/absent split once real records exist.
            attendanceChart.update();
        });

      } catch (e) { console.error('Attendance chart failed to load:', e); }
    </script>

    <script>
      try {
        // ---- Cash pool flow for the active term ----
        window.formatChartAmount = (rawValue) => {
            const value = Number(rawValue) || 0;
            const absolute = Math.abs(value);
            const compact = (divisor, suffix) => {
                const scaled = value / divisor;
                const decimals = Math.abs(scaled) >= 10 ? 0 : 1;
                return `${Number(scaled.toFixed(decimals))}${suffix}`;
            };
            if (absolute >= 1_000_000_000_000) return compact(1_000_000_000_000, 'T');
            if (absolute >= 1_000_000_000) return compact(1_000_000_000, 'B');
            if (absolute >= 1_000_000) return compact(1_000_000, 'M');
            if (absolute >= 1_000) return compact(1_000, 'K');
            return Math.round(value).toLocaleString('en-UG');
        };
        const revenueLabels = @json($cashFlowLabels);
        const revenueIncome = @json($cashFlowIncome);
        const revenueExpenditure = @json($cashFlowExpenditure);
        const revenueCtx = document.getElementById('revenueChart');
        document.getElementById('revenueTypeFilter').value = @json(request('revenue_type', 'both'));
        const initialRevenueType = document.getElementById('revenueTypeFilter').value;
        let revenueChart = new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: revenueLabels,
                datasets: [
                    { label: 'Income', data: revenueIncome, hidden: initialRevenueType === 'expenditure', backgroundColor: '#facc15', borderRadius: 5, maxBarThickness: 28 },
                    { label: 'Expenditure', data: revenueExpenditure, hidden: initialRevenueType === 'income', backgroundColor: '#252641', borderRadius: 5, maxBarThickness: 28 },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } },
                    tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: UGX ${Number(ctx.raw).toLocaleString('en-UG')}` } }
                },
                scales: { y: { beginAtZero: true, ticks: { callback: (value) => window.formatChartAmount(value) } } }
            }
        });
        document.getElementById('revenueTypeFilter').addEventListener('change', function () {
            const val = this.value;
            const url = new URL(window.location); url.searchParams.set('revenue_type', val); history.replaceState({}, '', url);
            revenueChart.data.datasets[0].hidden = val === 'expenditure';
            revenueChart.data.datasets[1].hidden = val === 'income';
            revenueChart.update();
        });
      } catch (e) { console.error('Revenue chart failed to load:', e); }
    </script>

    <script>
      try {
        // ---- Mini calendar ----
        const eventRecords = @json($dashboardEvents);
        let calDate = new Date();
        function renderCalendar() {
            const year = calDate.getFullYear();
            const month = calDate.getMonth();
            document.getElementById('calendarMonthLabel').textContent =
                calDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const grid = document.getElementById('calendarGrid');
            grid.innerHTML = '';

            for (let i = 0; i < firstDay; i++) {
                grid.innerHTML += '<span></span>';
            }
            const today = new Date();
            for (let d = 1; d <= daysInMonth; d++) {
                const isToday = today.getDate() === d && today.getMonth() === month && today.getFullYear() === year;
                const dateKey = `${year}-${String(month + 1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
                const hasEvent = eventRecords.some(event => event.event_date === dateKey);
                grid.innerHTML += `<span class="w-7 h-7 flex items-center justify-center rounded-full mx-auto relative
                    ${isToday ? 'bg-darken text-white font-medium' : 'text-gray-600'}">
                    ${d}
                    ${hasEvent ? '<span class="absolute bottom-0.5 w-1 h-1 rounded-full bg-yellow-500"></span>' : ''}
                </span>`;
            }

            const list = document.getElementById('calendarEventsList');
            list.innerHTML = eventRecords.filter(event => event.event_date.startsWith(`${year}-${String(month + 1).padStart(2,'0')}`)).map(event => `
                <div class="flex items-center space-x-2">
                    <span class="w-8 text-center text-xs font-medium bg-gray-100 rounded py-1 text-gray-600">${new Date(event.event_date+'T00:00:00').getDate()}</span>
                    <span class="text-gray-600">${escapeHtml(event.title)}</span>
                </div>
            `).join('') || '<p class="text-gray-400 text-xs">No events this month.</p>';
        }
        document.getElementById('calPrev').addEventListener('click', () => { calDate.setMonth(calDate.getMonth() - 1); renderCalendar(); });
        document.getElementById('calNext').addEventListener('click', () => { calDate.setMonth(calDate.getMonth() + 1); renderCalendar(); });
        renderCalendar();

      } catch (e) { console.error('Calendar failed to load:', e); }
    </script>

    <script>
      try {
        // ---- Timetable (sample) ----
        const slotRecords = @json($dashboardTimetable);
        const days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
        const timetable = [...new Set(slotRecords.map(slot => `${slot.starts_at}-${slot.ends_at}`))].map(time => {
            const [start,end] = time.split('-');
            return [`${start.slice(0,5)} - ${end.slice(0,5)}`, ...days.map(day => { const slot = slotRecords.find(item => `${item.starts_at}-${item.ends_at}` === time && item.day_of_week === day); return slot ? (slot.subject || slot.label || 'Lesson') : '—'; })];
        });
        document.getElementById('timetableBody').innerHTML = timetable.map(row => `
            <tr class="border-t border-gray-100">
                <td class="py-2 pr-2 font-medium text-gray-500 whitespace-nowrap">${escapeHtml(row[0])}</td>
                ${row.slice(1).map(subj => `<td class="py-2 px-1 text-center ${subj === 'Break' ? 'text-gray-300 italic' : ''}">${escapeHtml(subj)}</td>`).join('')}
            </tr>
        `).join('');

      } catch (e) { console.error('Timetable failed to load:', e); }
    </script>

    <script>
      try {
        // ---- Notifications (sample) ----
        const notifications = [
            { icon: '✉', text: 'New message from a parent regarding fee balance.', time: '2h ago' },
            { icon: '✓', text: 'Attendance was marked for all classes today.', time: '5h ago' },
            { icon: '◫', text: 'Term 1 fee deadline is in 3 days.', time: '1d ago' },
            { icon: '◈', text: 'A new staff account was added.', time: '2d ago' },
        ];
        const liveNotifications = @json($dashboardNotifications).map(notification => ({ icon: notification.type === 'warning' ? '!' : 'i', text: notification.title + ': ' + notification.message, time: new Date(notification.created_at).toLocaleDateString() }));
        if (liveNotifications.length) notifications.splice(0, notifications.length, ...liveNotifications);
        const renderNotifications = (n) => `
            <div class="flex items-start space-x-3 py-3 border-b border-gray-50 last:border-0 px-1">
                <span class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500 flex-shrink-0">${escapeHtml(n.icon)}</span>
                <div class="flex-1">
                    <p class="text-sm text-gray-600">${escapeHtml(n.text)}</p>
                    <p class="text-xs text-gray-400 mt-0.5">${escapeHtml(n.time)}</p>
                </div>
            </div>
        `;
        document.getElementById('notificationsList').innerHTML = notifications.map(renderNotifications).join('');
        document.getElementById('notificationsDropdown').innerHTML = notifications.map(renderNotifications).join('');
      } catch (e) { console.error('Notifications failed to load:', e); }
    </script>

    <script>
      try {
        // ---- Live reminders ----
        const reminders = @json($dashboardReminders);
        document.getElementById('remindersList').innerHTML = reminders.length ? reminders.map(r => `
            <div class="flex items-start space-x-3 py-3 border-b border-gray-50 last:border-0">
                <span class="w-8 h-8 rounded-lg bg-orange-50 text-orange-500 flex items-center justify-center flex-shrink-0">${escapeHtml(r.icon)}</span>
                <div class="flex-1">
                    <p class="text-sm text-gray-600">${escapeHtml(r.text)}</p>
                    <p class="text-xs text-orange-500 font-medium mt-0.5">${escapeHtml(r.due)}</p>
                </div>
            </div>
        `).join('') : '<p class="py-6 text-center text-xs text-gray-400">No upcoming events or deadlines.</p>';
      } catch (e) { console.error('Reminders failed to load:', e); }
    </script>

    <script>
      try {
        // ---- Greeting clock ----
        function updateGreetingClock() {
            const el = document.getElementById('greetingDateTime');
            if (!el) return;
            const now = new Date();
            const dateStr = now.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            const timeStr = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
            el.textContent = `Today is ${dateStr}, ${timeStr}`;
        }
        updateGreetingClock();
        setInterval(updateGreetingClock, 30000);
      } catch (e) { console.error('Greeting clock failed to load:', e); }
    </script>

    <livewire:profile-modal />

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('profile-updated', () => {
                setTimeout(() => window.location.reload(), 600);
            });
        });
    </script>
    <script>
        const genderCtx = document.getElementById('genderPieChart').getContext('2d');
new Chart(genderCtx, {
    type: 'pie',
    data: {
        labels: ['Boys', 'Girls'],
        datasets: [{
            data: @json($genderData),
            backgroundColor: [
                '#facc15', // Tailwind yellow-400
                '#252641'  // Tailwind amber-600
            ],
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 12,
                    font: { size: 11, weight: '500' },
                    color: '#374151'
                }
            }
        }
    }
});
    </script>

    <script>
        // 1. Payment Trend Line Chart
const paymentCtx = document.getElementById('paymentTrendChart').getContext('2d');
const paymentTrendDatasets = @json($paymentTrendByYear);
const paymentYearFilter = document.getElementById('paymentYearFilter');
paymentYearFilter.querySelectorAll('option').forEach(option => option.textContent = option.textContent.replace('Academic Year', 'Calendar Year'));
if (@json(request()->filled('payment_year'))) paymentYearFilter.value = @json((string) request('payment_year'));
const initialPaymentYear = paymentYearFilter.value;
const paymentTrendChart = new Chart(paymentCtx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: 'Collections',
            data: paymentTrendDatasets[initialPaymentYear] ?? [],
            borderColor: '#252641', // Dark primary
            backgroundColor: 'rgba(37, 38, 65, 0.05)',
            borderWidth: 3,
            tension: 0.3,
            fill: true,
            pointBackgroundColor: '#facc15', // Yellow point indicators
            pointBorderColor: '#252641',
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: (ctx) => `Collections: UGX ${Number(ctx.raw).toLocaleString('en-UG')}` } }
        },
        scales: { y: { beginAtZero: true, ticks: { callback: (value) => window.formatChartAmount(value) } } }
    }
});
paymentYearFilter.addEventListener('change', function () {
    const url = new URL(window.location); url.searchParams.set('payment_year', this.value); history.replaceState({}, '', url);
    paymentTrendChart.data.datasets[0].data = paymentTrendDatasets[this.value] ?? [];
    paymentTrendChart.update();
});

// 2. Class Performance Line Chart
const performanceCtx = document.getElementById('classPerformanceChart').getContext('2d');
const performanceDatasets = @json($performanceSeries);
const performanceClassFilter = document.getElementById('performanceClassFilter');
performanceClassFilter.value = @json((string) request('performance_class', 'all'));
const initialPerformanceSeries = performanceDatasets[performanceClassFilter.value] ?? performanceDatasets.all;
const classPerformanceChart = new Chart(performanceCtx, {
    type: 'line',
    data: {
        labels: initialPerformanceSeries.labels,
        datasets: [{
            label: 'Class Average',
            data: initialPerformanceSeries.data,
            borderColor: '#facc15', // Yellow primary
            backgroundColor: 'rgba(250, 204, 21, 0.05)',
            borderWidth: 3,
            tension: 0.2,
            fill: true,
            pointBackgroundColor: '#252641', // Dark point indicators
            pointBorderColor: '#facc15',
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, max: 100 } }
    }
});
performanceClassFilter.addEventListener('change', function () {
    const url = new URL(window.location); url.searchParams.set('performance_class', this.value); history.replaceState({}, '', url);
    const series = performanceDatasets[this.value] ?? { labels: [], data: [] };
    classPerformanceChart.data.labels = series.labels;
    classPerformanceChart.data.datasets[0].data = series.data;
    classPerformanceChart.update();
});
    </script>

    <script>
        // Current-term debtors, mapped directly from the finance records.
        const liveDebtors = @json($dashboardDebtors);
        const currencySymbol = @json($currencySymbol);
        function refreshDebtorsSpotlight() {
            const grid = document.getElementById('debtorsSpotlightGrid');
            grid.innerHTML = liveDebtors.length ? liveDebtors.map(debtor => `<div class="p-4 rounded-xl border border-gray-100 bg-gray-50/40 flex items-center justify-between gap-3"><div><p class="font-bold text-gray-900 text-sm">${escapeHtml(debtor.name)}</p><p class="text-xs text-gray-400">${escapeHtml(debtor.class)}</p></div><div class="flex items-center gap-2"><span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-[#252641] text-[#facc15]">${escapeHtml(currencySymbol)} ${Number(debtor.balance).toLocaleString()}</span><a title="View learner profile" href="{{ route('students.index') }}?student=${Number(debtor.id)}" class="rounded-lg border px-2 py-1 text-xs">Profile</a><a title="Pay this learner's fees" href="{{ route('fee-payments.index') }}?student=${Number(debtor.id)}" class="rounded-lg border px-2 py-1 text-xs">Pay</a>${debtor.guardian_email ? `<a title="Send arrears reminder" href="mailto:${encodeURIComponent(String(debtor.guardian_email))}?subject=${encodeURIComponent('Fee balance reminder')}&body=${encodeURIComponent('Dear parent/guardian, please contact the school regarding the outstanding balance for '+String(debtor.name)+'.')}" class="rounded-lg border px-2 py-1 text-xs">Email</a>` : ''}</div></div>`).join('') : '<p class="col-span-full p-5 text-sm text-gray-500">No active-term debtors.</p>';
        }
        refreshDebtorsSpotlight();
    </script>

    <script>
        const refreshDashboardCharts = () => setTimeout(() => window.dispatchEvent(new Event('resize')), 150);
        window.addEventListener('load', refreshDashboardCharts);
        document.addEventListener('livewire:navigated', refreshDashboardCharts);
    </script>

    @livewireScripts

    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-4 sm:px-6 lg:px-10 py-3 flex items-center justify-center z-20 shadow-sm">
    <p class="text-xs text #252641 text-center">
        Copyright &copy; {{ date('Y') }} <span class="font-semibold text-yellow-400">{{ config('app.name', 'Edlink') }}</span>. All rights reserved. 
        <a href="{{ url('/') }}" class="text-yellow-400 hover:underline">
            Spotnet Technologies
        </a>
    </p>
</div>
</body>

</html>
