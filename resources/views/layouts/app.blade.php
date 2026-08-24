<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Edlink' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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
        [x-cloak] { display: none !important; }
        html, body { overflow-x: hidden; max-width: 100vw; }
        
        :root { --sidebar-w: 288px; }
        #app-sidebar { width: var(--sidebar-w); transition: width .2s ease; }
        #app-main { transition: margin-left .2s ease; }
        @media (min-width: 1024px) { #app-main { margin-left: var(--sidebar-w); } }

        /* Navigation Links styles */
        .nav-link, .nav-group-btn { color: #d1d5db; transition: background-color .15s ease, color .15s ease; }
        .nav-link:hover, .nav-group-btn:hover { background-color: rgba(234, 179, 8, 0.12); color: #facc15; }
        
        /* Active States */
        .nav-link.active, .nav-group-btn.active { background-color: #eab308; color: #252641; font-weight: 600; }
        .nav-link.active svg, .nav-group-btn.active svg { color: #252641; }
        
        /* Sub-navigation Links */
        .nav-sub-link { color: #9ca3af; transition: color .15s ease; position: relative; }
        .nav-sub-link:hover { color: #facc15; }
        .nav-sub-link.active { color: #facc15; font-weight: 600; }
        .nav-sub-link.active::before {
            content: '';
            position: absolute;
            left: -16px;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 4px;
            background-color: #facc15;
            border-radius: 50%;
        }
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
    <aside id="app-sidebar" class="bg-darken text-white flex-col fixed inset-y-0 z-40 transform lg:translate-x-0 flex overflow-y-auto overflow-x-hidden border-r border-white/5 shadow-2xl"
        :class="mobileNavOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        x-data="{ open: '' }">

        <!-- Logo Container -->
        <div class="px-6 py-6 flex items-center space-x-2 border-b border-white/10 hidden lg:flex" :class="$store.ui.collapsed && 'justify-center px-0'">
            <span class=" shrink-0">
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

        <!-- Navigation Menu -->
        <nav class="flex-1 px-3 py-6 space-y-1 text-sm mt-14 lg:mt-0">

            <a href="{{ route(auth()->user()->portalHomeRoute()) }}" wire:navigate @click="open = ''" class="nav-link {{ request()->routeIs('dashboard', 'workbench.home') ? 'active' : '' }} flex items-center space-x-3 px-3 py-2.5 rounded-lg" :class="$store.ui.collapsed && 'justify-center'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                <span x-show="!$store.ui.collapsed" x-cloak>Dashboard</span>
            </a>

            @if(in_array(auth()->user()->role, ['student', 'parent'], true))
                <a href="{{ route('my-results') }}" wire:navigate class="nav-link {{ request()->routeIs('my-results') ? 'active' : '' }} flex items-center space-x-3 px-3 py-2.5 rounded-lg" :class="$store.ui.collapsed && 'justify-center'">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 014-4h4M9 17H7a2 2 0 01-2-2V7a2 2 0 012-2h6l4 4v6a2 2 0 01-2 2h-2" /></svg>
                    <span x-show="!$store.ui.collapsed" x-cloak>My Results</span>
                </a>
            @endif

            @if(in_array(auth()->user()->role, ['student', 'parent'], true))
                <a href="{{ route('portal.home') }}#attendance" class="nav-link flex items-center space-x-3 px-3 py-2.5 rounded-lg" :class="$store.ui.collapsed && 'justify-center'"><svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span x-show="!$store.ui.collapsed" x-cloak>{{ auth()->user()->role === 'parent' ? 'Learner Attendance' : 'My Attendance' }}</span></a>
                <a href="{{ route('portal.home') }}#performance" class="nav-link flex items-center space-x-3 px-3 py-2.5 rounded-lg" :class="$store.ui.collapsed && 'justify-center'"><svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13h4v8H3v-8zm7-5h4v13h-4V8zm7-5h4v18h-4V3z" /></svg><span x-show="!$store.ui.collapsed" x-cloak>{{ auth()->user()->role === 'parent' ? 'Learner Performance' : 'My Performance' }}</span></a>
                @if(auth()->user()->role === 'parent')<a href="{{ route('portal.home') }}#payments" class="nav-link flex items-center space-x-3 px-3 py-2.5 rounded-lg" :class="$store.ui.collapsed && 'justify-center'"><svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-10v2m0 10v2m9-8a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span x-show="!$store.ui.collapsed" x-cloak>Fees & Payments</span></a>@endif
                <a href="{{ route('portal.home') }}#timetable" class="nav-link flex items-center space-x-3 px-3 py-2.5 rounded-lg" :class="$store.ui.collapsed && 'justify-center'"><svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" /></svg><span x-show="!$store.ui.collapsed" x-cloak>Timetable & Events</span></a>
                @if(auth()->user()->role === 'student')<a href="{{ route('homework.index') }}" wire:navigate class="nav-link {{ request()->routeIs('homework.*') ? 'active' : '' }} flex items-center space-x-3 px-3 py-2.5 rounded-lg" :class="$store.ui.collapsed && 'justify-center'"><svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6M7 3h7l5 5v13H7a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg><span x-show="!$store.ui.collapsed" x-cloak>Homework</span></a>@endif
            @endif

            @if(in_array(auth()->user()->role, ['teacher', 'bursar', 'registrar', 'academic_admin'], true) && ! auth()->user()->hasPermission('staff.leaves'))
                <a href="{{ route('leaves.index') }}" wire:navigate class="nav-link {{ request()->routeIs('leaves.index') ? 'active' : '' }} flex items-center space-x-3 px-3 py-2.5 rounded-lg" :class="$store.ui.collapsed && 'justify-center'"><svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 3h7l5 5v13H7a2 2 0 01-2-2V5a2 2 0 012-2z" /></svg><span x-show="!$store.ui.collapsed" x-cloak>My Leave</span></a>
            @endif

            @if(in_array(auth()->user()->role, ['teacher', 'academic_admin'], true) && \Illuminate\Support\Facades\Schema::hasTable('student_clubs') && (\Illuminate\Support\Facades\DB::table('student_clubs')->where('school_id', auth()->user()->school_id)->where('patron_user_id', auth()->id())->exists() || \Illuminate\Support\Facades\DB::table('student_houses')->where('school_id', auth()->user()->school_id)->where('patron_user_id', auth()->id())->exists()) && ! auth()->user()->hasPermission('students.activities'))
                <a href="{{ route('students.activities') }}" wire:navigate class="nav-link {{ request()->routeIs('students.activities') ? 'active' : '' }} flex items-center space-x-3 px-3 py-2.5 rounded-lg" :class="$store.ui.collapsed && 'justify-center'"><svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7l9-4 9 4-9 4-9-4zm2 4v6l7 4 7-4v-6" /></svg><span x-show="!$store.ui.collapsed" x-cloak>My Clubs & House</span></a>
            @endif
            @if(\App\Support\TeacherAcademicScope::isTeacher(auth()->user()) && \Illuminate\Support\Facades\Schema::hasColumn('school_classes','class_teacher_user_id') && \App\Support\TeacherAcademicScope::classIds(auth()->user())->isNotEmpty() && !auth()->user()->hasPermission('students.view'))
                <a href="{{ route('students.index') }}" wire:navigate class="nav-link {{ request()->routeIs('students.index') ? 'active' : '' }} flex items-center space-x-3 px-3 py-2.5 rounded-lg" :class="$store.ui.collapsed && 'justify-center'"><svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0v7" /></svg><span x-show="!$store.ui.collapsed" x-cloak>My Class Students</span></a>
            @endif
            @foreach([
                'students' => ['label' => 'Students', 'icon' => 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0121 17.5c0 .34-.02.675-.06 1.004M12 14l-6.16-3.422A12.083 12.083 0 003 17.5c0 .34.02.675.06 1.004M12 14v7', 'routes' => ['students.register', 'students.index', 'graduates.index', 'student-categories.index', 'students.activities', 'students.portal-access', 'promotions.index'], 'items' => [
                    ['label' => 'Registration', 'route' => 'students.register'],
                    ['label' => 'All Students', 'route' => 'students.index'],
                    ['label' => 'Graduates & Alumni', 'route' => 'graduates.index'],
                    ['label' => 'Categories', 'route' => 'student-categories.index'],
                    ['label' => 'Houses & Clubs', 'route' => 'students.activities'],
                    ['label' => 'Portal Access', 'route' => 'students.portal-access'],
                    ['label' => 'Promotions', 'route' => 'promotions.index'],
                    ['label' => 'ID Cards', 'route' => 'students.id-cards'],
                ]],
                'academics' => ['label' => 'Academics', 'icon' => 'M4 6h16M4 12h16M4 18h7', 'routes' => ['classes.index', 'subjects.index', 'subject-selections.index', 'grading-scales.index', 'timetable.index', 'homework.index', 'events.index'], 'items' => [
                    ['label' => 'Classes & Streams', 'route' => 'classes.index'],
                    ['label' => 'Subjects', 'route' => 'subjects.index'],
                    ['label' => 'Grading Scales', 'route' => 'grading-scales.index'],
                    ['label' => 'Student Subject Selection', 'route' => 'subject-selections.index'],
                    ['label' => 'Timetable', 'route' => 'timetable.index'],
                    ['label' => 'Homework', 'route' => 'homework.index'],
                    ['label' => 'Events', 'route' => 'events.index'],
                ]],
                'attendance' => ['label' => 'Attendance', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'routes' => ['attendance.index', 'attendance.subject', 'attendance.reports'], 'items' => [
                    ['label' => 'Mark Attendance', 'route' => 'attendance.index'],
                    ['label' => 'Subject Attendance', 'route' => 'attendance.subject'],
                    ['label' => 'Attendance Reports', 'route' => 'attendance.reports'],
                ]],
                'finance' => ['label' => 'Finance', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v2m9-8a9 9 0 11-18 0 9 9 0 0118 0z', 'routes' => ['fee-structures.index', 'fee-payments.index', 'expenses.index', 'terms.index', 'finance.ledger'], 'items' => [
                    ['label' => 'Terms', 'route' => 'terms.index'],
                    ['label' => 'Fee Structure', 'route' => 'fee-structures.index'],
                    ['label' => 'Payments', 'route' => 'fee-payments.index'],
                    ['label' => 'Expenses', 'route' => 'expenses.index'],
                    ['label' => 'Ledger & Reconciliation', 'route' => 'finance.ledger'],
                ]],
                'exams' => ['label' => 'Exams', 'icon' => 'M9 17v-2a4 4 0 014-4h4M9 17H7a2 2 0 01-2-2V7a2 2 0 012-2h6l4 4v6a2 2 0 01-2 2h-2', 'routes' => ['exams.setup', 'exams.marks', 'exams.results'], 'items' => [
                    ['label' => 'Create Exam', 'route' => 'exams.setup'],
                    ['label' => 'Enter Marks', 'route' => 'exams.marks'],
                    ['label' => 'Report Cards', 'route' => 'exams.results'],
                ]],
                'staff' => ['label' => 'Staff', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-2.13a4 4 0 10-4-4 4 4 0 0017 8z', 'routes' => ['staff.index', 'staff.register', 'staff.attendance', 'payroll.index', 'leaves.index', 'designations.index'], 'items' => [
                    ['label' => 'All Staff', 'route' => 'staff.index'],
                    ['label' => 'Add Staff', 'route' => 'staff.register'],
                    ['label' => 'Staff Attendance', 'route' => 'staff.attendance'],
                    ['label' => 'Payroll', 'route' => 'payroll.index'],
                    ['label' => 'Leave Requests', 'route' => 'leaves.index'],
                    ['label' => 'Designations & Access', 'route' => 'designations.index'],
                ]],
                'parents' => ['label' => 'Parents', 'icon' => 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l1.586-1.586z', 'routes' => [], 'items' => [
                    ['label' => 'Register Parent', 'route' => 'parents.register'],
                    ['label' => 'All Parents', 'route' => 'parents.index'],
                    ['label' => 'Communications', 'route' => 'communications.index'],
                ]],
            ] as $key => $group)
                @continue(in_array(auth()->user()->role, ['student', 'parent'], true))
                @continue(! auth()->user()->hasModuleAccess($key))
                @php
                    $isGroupActive = false;
                    foreach($group['routes'] as $r) {
                        if(request()->routeIs($r)) { $isGroupActive = true; break; }
                    }
                    if ($key === 'parents' && request()->routeIs('parents.*')) { $isGroupActive = true; }
                @endphp
                <div x-init="if({{ $isGroupActive ? 'true' : 'false' }}) open = '{{ $key }}'">
                    <button @click="if($store.ui.collapsed){$store.ui.toggle();open='{{ $key }}'}else{open = open === '{{ $key }}' ? '' : '{{ $key }}'}" 
                        class="nav-group-btn w-full flex items-center px-3 py-2.5 rounded-lg {{ $isGroupActive ? 'active' : '' }}" 
                        :class="[$store.ui.collapsed ? 'justify-center' : 'justify-between', open === '{{ $key }}' && !$store.ui.collapsed && !{{ $isGroupActive ? 'true' : 'false' }} && 'bg-yellow-500/10 text-yellow-400']">
                        
                        <span class="flex items-center space-x-3" :class="$store.ui.collapsed && 'space-x-0'">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $group['icon'] }}" /></svg>
                            <span x-show="!$store.ui.collapsed" x-cloak>{{ $group['label'] }}</span>
                        </span>
                        <svg x-show="!$store.ui.collapsed" x-cloak class="w-4 h-4 transition-transform" :class="open === '{{ $key }}' && 'rotate-90'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </button>
                    
                    <div x-cloak x-show="open === '{{ $key }}' && !$store.ui.collapsed" class="pl-11 pr-3 py-1 space-y-1">
                        @foreach($group['items'] as $item)
                            @continue($item['route'] === 'subject-selections.index' && auth()->user()->school?->school_type !== 'secondary')
                            @php($requiredPermission = match($item['route']) {
                                'students.index', 'graduates.index' => 'students.view', 'students.register', 'student-categories.index', 'students.portal-access' => 'students.manage', 'students.activities' => 'students.activities',
                                'fee-payments.index' => 'finance.payments', 'expenses.index' => 'finance.expenses', 'finance.ledger' => 'finance.ledger',
                                'attendance.index' => 'attendance.daily', 'attendance.subject' => 'attendance.subject', 'attendance.reports' => 'attendance.reports',
                                'classes.index' => 'academics.classes', 'subjects.index', 'subject-selections.index' => 'academics.subjects', 'timetable.index' => 'academics.timetable', 'events.index' => 'academics.events',
                                'exams.setup' => 'exams.setup', 'exams.marks' => 'exams.marks', 'exams.results' => 'exams.results',
                                'staff.index' => 'staff.directory', 'staff.register' => 'staff.manage', 'staff.attendance' => 'staff.attendance', 'payroll.index' => 'staff.payroll', 'leaves.index' => 'staff.leaves', 'designations.index' => 'staff.designations',
                                'parents.index', 'parents.register' => 'parents.manage', 'communications.index' => 'parents.communications',
                                default => null,
                            })
                            @continue($requiredPermission && ! auth()->user()->hasPermission($requiredPermission))
                            @if($item['route'])
                                <a href="{{ route($item['route']) }}" wire:navigate class="nav-sub-link {{ request()->routeIs($item['route']) ? 'active' : '' }} block py-1.5 text-sm">{{ $item['label'] }}</a>
                            @else
                                <a href="#" class="nav-sub-link block py-1.5 text-sm">{{ $item['label'] }}</a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if(in_array(auth()->user()->role, ['admin', 'superadmin'], true))
            <a href="{{ route('settings.audit-trail') }}" wire:navigate class="nav-link {{ request()->routeIs('settings.audit-trail') ? 'active' : '' }} flex items-center space-x-3 px-3 py-2.5 rounded-lg" :class="$store.ui.collapsed && 'justify-center space-x-0'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2zM14 3v5h5" /></svg>
                <span x-show="!$store.ui.collapsed" x-cloak>Audit Trail</span>
            </a>
            @endif
            @if(auth()->user()->hasPermission('reports.view'))
            <a href="{{ route('reports.index') }}" wire:navigate class="nav-link {{ request()->routeIs('reports.index') ? 'active' : '' }} flex items-center space-x-3 px-3 py-2.5 rounded-lg" :class="$store.ui.collapsed && 'justify-center space-x-0'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                <span x-show="!$store.ui.collapsed" x-cloak>Reports</span>
            </a>
            @endif
            @if(auth()->user()->hasPermission('parents.communications'))
            <a href="{{ route('communications.index') }}" wire:navigate class="nav-link flex items-center space-x-3 px-3 py-2.5 rounded-lg" :class="$store.ui.collapsed && 'justify-center space-x-0'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
                <span x-show="!$store.ui.collapsed" x-cloak>Announcements</span>
            </a>
            @endif
        </nav>

        <!-- Sidebar Footer Action Links -->
        <div class="px-3 py-5 border-t border-white/10 space-y-1">
            <button @click="$store.ui.toggle()" class="nav-link hidden lg:flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm w-full" :class="$store.ui.collapsed && 'justify-center space-x-0'">
                <svg class="w-5 h-5 flex-shrink-0 transition-transform" :class="$store.ui.collapsed && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" /></svg>
                <span x-show="!$store.ui.collapsed" x-cloak>Collapse</span>
            </button>
            @if(auth()->user()->hasPermission('settings.manage'))
            <a href="{{ route('settings.index') }}" wire:navigate class="nav-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm w-full text-left" :class="$store.ui.collapsed && 'justify-center space-x-0'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                <span x-show="!$store.ui.collapsed" x-cloak>Settings</span>
            </a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm w-full text-left transition-colors hover:bg-red-500/10 hover:text-red-400" :class="$store.ui.collapsed && 'justify-center space-x-0'">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    <span x-show="!$store.ui.collapsed" x-cloak>Log out</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Overlay backdrop for mobile view -->
    <div x-cloak x-show="mobileNavOpen" @click="mobileNavOpen = false" class="fixed inset-0 bg-black/40 z-30 lg:hidden transition-opacity"></div>

    <!-- Main Content Wrapper -->
    <div id="app-main" class="flex-1 pt-14 lg:pt-0">

        <!-- Top Header Navigation Bar -->
        <header class="bg-white border-b border-gray-200 px-4 sm:px-6 lg:px-10 py-3 flex items-center justify-between gap-3 sticky top-0 z-20 shadow-sm">
            <div class="flex items-center space-x-4 min-w-0">
                <button @click="$store.ui.toggle()" class="hidden lg:flex w-9 h-9 rounded-lg border border-gray-200 items-center justify-center text-gray-500 hover:bg-gray-50 hover:border-gray-300 transition-colors flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <div class="min-w-0">
                    <h1 class="text-sm font-semibold text-darken truncate">{{ $pageTitle ?? (auth()->user()->school->name ?? 'Edlink') }}</h1>
                    <p class="text-xs text-gray-400 truncate">{{ auth()->user()->school->school_number ?? '' }}</p>
                </div>
            </div>

            <!-- Profile & Search Bar Panel Layout -->
            @include('partials.branch-switcher')
            <div class="flex items-center space-x-2 lg:space-x-4 flex-shrink-0">
                 @if(isset($school) && $school?->is_demo)
                    <span class="text-xs bg-yellow-100 text-yellow-700 px-3 py-1.5 rounded-full font-medium hidden sm:inline-block">
                        Demo — expires {{ $school->demo_expires_at?->diffForHumans() }}
                    </span>
                @endif
                
                <div class="relative hidden md:block">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <input type="text" placeholder="Search students, classes..." class="w-48 lg:w-64 pl-9 pr-4 py-2 text-sm bg-gray-100 border border-transparent rounded-full focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:bg-white transition-colors">
                </div>

                <!-- Notification bell and hover preview -->
                <div class="group relative isolate">
                    <a href="{{ route('notifications.index') }}" wire:navigate aria-label="Open notification center" aria-describedby="notificationsDropdownPanel" title="Notifications" class="relative flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-amber-50 hover:text-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                        @if(collect($layoutNotifications ?? [])->whereNull('read_at')->isNotEmpty())<span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>@endif
                    </a>

                    <div id="notificationsDropdownPanel" role="tooltip" class="invisible absolute right-0 top-full z-[100] w-80 pt-2 opacity-0 transition duration-150 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl">
                            <div class="px-4 py-2 border-b border-gray-100 flex items-center justify-between">
                                <p class="text-sm font-semibold text-darken">Notifications</p>
                                <a href="{{ route('notifications.index') }}" wire:navigate class="text-xs text-yellow-700 font-bold hover:text-yellow-900">View all · {{ collect($layoutNotifications ?? [])->whereNull('read_at')->count() }} new</a>
                            </div>
                            <div id="notificationsDropdown" class="block max-h-[70vh] min-h-12 divide-y divide-gray-100 overflow-y-auto overscroll-contain bg-white">
                                @forelse($layoutNotifications ?? collect() as $notification)
                                    <a href="{{ route('notifications.index') }}" wire:navigate class="flex min-h-20 items-start gap-3 px-4 py-3 hover:bg-slate-50 {{ $notification->read_at ? 'bg-white' : 'bg-amber-50/40' }}">
                                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg font-bold {{ $notification->type === 'warning' ? 'bg-amber-100 text-amber-700' : ($notification->type === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700') }}">
                                            {{ $notification->type === 'warning' ? '!' : ($notification->type === 'success' ? '✓' : 'i') }}
                                        </span>
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

                <!-- Account dropdown settings menu details -->
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

        <!-- Main Slot Blade View Grid Area Layout -->
        <main class="p-6 lg:p-10">
            @isset($slot)
                {{ $slot }}
            @else
                @yield('content')
            @endisset
        </main>
    </div>

    <livewire:profile-modal />
    <livewire:student-edit-modal />

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('profile-updated', () => setTimeout(() => window.location.reload(), 600));
        });
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
