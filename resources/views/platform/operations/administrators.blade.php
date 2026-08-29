@extends('layouts.platform', ['title' => 'Platform Administrators'])

@section('content')
<div class="space-y-6">

    <!-- Page Header & Action Title -->
    <div class="sm:flex sm:items-center sm:justify-between border-b border-slate-900 pb-6">
        <div>
            <h1 class="text-2xl font-bold text-amber-300 tracking-tight">Platform Administrators</h1>
            <p class="mt-1 text-sm text-slate-200">Manage administrative privileges, credentials, and access roles across the platform.</p>
        </div>
    </div>

    <!-- ================= TOP STATS HEADER CARDS ================= -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Total Administrators Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Administrators</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ $admins->count() }}</p>
            </div>
            <div class="h-11 w-11 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
        </div>

        <!-- Active Users Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Accounts</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $admins->where('is_active', true)->count() }}</p>
            </div>
            <div class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <!-- Platform Owners Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Platform Owners</p>
                <p class="text-2xl font-bold text-indigo-600 mt-1">{{ $admins->where('role', 'platform_owner')->count() }}</p>
            </div>
            <div class="h-11 w-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Main Section: List & Creation Form -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        
        <!-- Left: Administrator List (2 Columns) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h2 class="text-base font-semibold text-slate-800">Active Administrators</h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                    {{ $admins->count() }} {{ Str::plural('User', $admins->count()) }}
                </span>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($admins as $admin)
                    <div class="px-6 py-4 flex items-center justify-between hover:bg-slate-50/60 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center font-bold text-indigo-600 text-sm">
                                {{ strtoupper(substr($admin->name, 0, 2)) }}
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">{{ $admin->name }}</h3>
                                <p class="text-xs text-slate-500">{{ $admin->email ?? 'No email provided' }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <!-- Role Badge -->
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-700 capitalize border border-slate-200/60">
                                {{ str_replace('_', ' ', $admin->role) }}
                            </span>

                            <!-- Status Badge -->
                            @if($admin->is_active)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-500 border border-slate-200/60">
                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                    Inactive
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-12 text-center text-slate-500 text-sm">
                        No administrators found. Create one using the form.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right: Add Administrator Form (1 Column) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
            <div class="mb-6">
                <h2 class="text-lg font-bold text-slate-900">Add Administrator</h2>
                <p class="text-xs text-slate-500 mt-1">Grant new user access to the platform management dashboard.</p>
            </div>

            <form method="POST" action="{{ route('platform.administrators.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Full Name</label>
                    <input type="text" id="name" name="name" required placeholder="e.g. Sarah Jenkins"
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="sarah@edlink.com"
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                </div>

                <div>
                    <label for="role" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Access Role</label>
                    <select id="role" name="role" required
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                        <option value="operations_admin">Operations Admin</option>
                        <option value="support_admin">Support Admin</option>
                        <option value="platform_owner">Platform Owner</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <div>
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Password</label>
                        <input type="password" id="password" name="password" required placeholder="••••••••"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Confirm</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="••••••••"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" 
                        class="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-white bg-slate-900 rounded-xl hover:bg-slate-800 active:scale-[0.98] transition-all shadow-sm">
                        Add Administrator
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
@endsection