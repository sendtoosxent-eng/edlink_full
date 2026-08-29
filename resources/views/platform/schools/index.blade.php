@extends('layouts.platform', ['title' => 'Schools Management'])

@section('content')
<div class="space-y-6">
    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
    @endif
    <!-- Header Block with Dark Gradient Background & Ambient Glow -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-sm">
        <div class="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-400/10 border border-amber-400/20 text-amber-300 text-xs font-bold mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h5m-5 0V11m0 0h5m-5 0H7" />
                    </svg>
                    <span>Platform Management</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-amber-300">
                    Schools & Tenant Capacity
                </h1>
                <p class="text-sm font-medium text-slate-400 mt-1.5 leading-relaxed">
                    Search registered school tenants, monitor real-time learner capacities, and audit subscription plan utilization.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
            <a href="{{ route('platform.schools.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/10 px-5 py-3 text-xs font-extrabold text-white transition hover:bg-white/15">
                <span class="text-base leading-none">+</span><span>Add school</span>
            </a>
            <a href="{{ route('platform.licences') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-400 hover:bg-amber-300 active:scale-95 text-slate-950 font-extrabold text-xs px-5 py-3 transition shadow-md hover:shadow-lg shrink-0">
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5z" />
                </svg>
                <span>Manage Subscriptions</span>
            </a>
            </div>
        </div>
        
        <!-- Decorative Ambient Glow -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Filter & Search Bar Card -->
    <section class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 sm:p-5">
        <form method="GET" action="{{ url()->current() }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_180px_180px_auto] items-center">
            <!-- Search Field -->
            <div class="relative">
                <input name="search" value="{{ $search }}" placeholder="Search school name or number..." 
                       class="w-full text-sm font-medium bg-white border border-slate-200 rounded-xl pl-9 pr-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs placeholder:text-slate-400">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <!-- Package Select Dropdown -->
            <div class="relative">
                <select name="plan" 
                        class="w-full text-xs font-bold bg-white border border-slate-200 rounded-xl pl-3.5 pr-9 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs appearance-none cursor-pointer">
                    <option value="">All Packages</option>
                    @foreach(['basic','premium','enterprise'] as $plan)
                        <option value="{{ $plan }}" @selected(request('plan') === $plan)>{{ ucfirst($plan) }}</option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            <!-- Status Select Dropdown -->
            <div class="relative">
                <select name="status" 
                        class="w-full text-xs font-bold bg-white border border-slate-200 rounded-xl pl-3.5 pr-9 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs appearance-none cursor-pointer">
                    <option value="">All Statuses</option>
                    @foreach(['active','trial','suspended','expired'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            <!-- Filter Action Button -->
            <button type="submit" 
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 active:scale-95 text-white font-extrabold text-xs px-6 py-2.5 rounded-xl transition shadow-xs cursor-pointer h-[42px]">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                <span>Filter</span>
            </button>
        </form>
    </section>

    <!-- Schools Table Matrix Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-left text-xs">
                <thead class="bg-slate-900 text-white font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="px-5 py-3.5">School Details</th>
                        <th class="px-5 py-3.5">Package Plan</th>
                        <th class="px-5 py-3.5">Active Learners</th>
                        <th class="px-5 py-3.5">Capacity Usage</th>
                        <th class="px-5 py-3.5">Subscription Status</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
                    @forelse($schools as $school)
                        @php
                            $limit = \App\Support\SubscriptionPlans::limit($school->license_plan);
                            $usage = $limit ? min(100, round(($school->active_students_count / $limit) * 100)) : 0;
                            $suggested = \App\Support\SubscriptionPlans::suggestedFor($school->active_students_count);
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors group">
                            <!-- School Name & Meta -->
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-900 font-black text-sm flex items-center justify-center shrink-0 shadow-2xs">
                                        {{ strtoupper(substr($school->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <b class="block font-bold text-slate-900 text-sm truncate">{{ $school->name }}</b>
                                        <span class="text-[11px] font-semibold text-slate-400 font-mono">
                                            {{ $school->school_number }} · {{ ucfirst($school->school_type) }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Package Plan -->
                            <td class="px-5 py-4">
                                <div class="inline-flex flex-col items-start gap-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg bg-amber-50 border border-amber-200/80 text-amber-900 text-xs font-bold capitalize">
                                        {{ $school->license_plan }}
                                    </span>
                                    @if($suggested !== $school->license_plan)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-100">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                            Suggested: {{ ucfirst($suggested) }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Active Learners -->
                            <td class="px-5 py-4">
                                <span class="text-sm font-black text-slate-900">
                                    {{ number_format($school->active_students_count) }}
                                </span>
                            </td>

                            <!-- Capacity Progress -->
                            <td class="px-5 py-4">
                                <div class="w-40 space-y-1.5">
                                    <div class="flex items-center justify-between text-[11px] font-bold">
                                        <span class="text-slate-400">{{ $limit ? number_format($limit) : 'Unlimited' }}</span>
                                        <span class="{{ $usage >= 90 ? 'text-rose-600' : ($usage >= 70 ? 'text-amber-600' : 'text-slate-700') }}">
                                            {{ $limit ? $usage.'%' : 'Flexible' }}
                                        </span>
                                    </div>
                                    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100 border border-slate-200/60 p-0.5">
                                        <div class="h-full rounded-full transition-all duration-300 {{ $usage >= 90 ? 'bg-rose-500' : ($usage >= 70 ? 'bg-amber-400' : 'bg-slate-900') }}" 
                                             style="width: {{ $limit ? $usage : 100 }}%"></div>
                                    </div>
                                </div>
                            </td>

                            <!-- License Status Badge -->
                            <td class="px-5 py-4">
                                @if($school->license_status === 'active')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold capitalize">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        {{ $school->license_status }}
                                    </span>
                                @elseif($school->license_status === 'trial')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold capitalize">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        {{ $school->license_status }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold capitalize">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        {{ $school->license_status }}
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('platform.schools.show', $school) }}"
                                   class="inline-flex items-center gap-1 text-xs font-extrabold text-slate-900 hover:text-amber-600 transition-colors">
                                    <span>View details</span>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 px-6 text-center">
                                <div class="flex flex-col items-center justify-center max-w-xs mx-auto space-y-2">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-xl shadow-2xs">
                                        <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h5m-5 0V11m0 0h5m-5 0H7" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold text-slate-800">No Schools Found</p>
                                    <p class="text-xs font-medium text-slate-400">No school accounts match your search filters or capacity constraints.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Table Footer / Pagination Link Area -->
        @if($schools->hasPages())
            <div class="p-4 bg-slate-50/80 border-t border-slate-100">
                {{ $schools->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
