@extends('layouts.platform', ['title' => 'Licences & Plans'])

@section('content')
<div class="space-y-6 pb-12">
    <!-- Header Block with Dark Gradient Background & Ambient Glow -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-sm">
        <div class="relative z-10 max-w-2xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-400/10 border border-amber-400/20 text-amber-300 text-xs font-bold mb-3">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5z" />
                </svg>
                <span>Subscriptions & Licensing</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-amber-300">
                Packages & School Capacity
            </h1>
            <p class="text-sm font-medium text-slate-400 mt-1.5 leading-relaxed">
                Configure subscription plan thresholds, manage active validity dates, and modify school licensing statuses.
            </p>
        </div>
        
        <!-- Decorative Ambient Glow -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Status Alert Notification -->
    @if(session('status'))
        <div class="flex items-center justify-between gap-3 bg-emerald-50 border border-emerald-200/80 text-emerald-900 text-sm rounded-2xl p-4 shadow-xs">
            <div class="flex items-center gap-3">
                <div class="p-1 bg-emerald-100 rounded-lg shrink-0">
                    <svg class="w-5 h-5 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="font-semibold">{{ session('status') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    @endif

    <!-- Section 1: Plan Packages Summary Cards -->
    <section class="grid gap-4 md:grid-cols-3">
        @foreach($plans as $key => $plan)
            <article class="relative rounded-2xl bg-white p-6 transition-all duration-200 border shadow-xs flex flex-col justify-between {{ $key === 'premium' ? 'border-amber-400 ring-2 ring-amber-400/20 shadow-md' : 'border-slate-200/80 hover:border-slate-300' }}">
                @if($key === 'premium')
                    <div class="absolute -top-3 right-6 bg-amber-400 text-slate-950 font-black text-[10px] uppercase tracking-wider px-3 py-0.5 rounded-full shadow-2xs">
                        Most Popular
                    </div>
                @endif
                <div>
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <h3 class="text-lg font-black text-slate-900 capitalize">{{ $plan['name'] }}</h3>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider {{ $key === 'premium' ? 'bg-amber-400 text-slate-950' : 'bg-slate-900 text-white' }}">
                            {{ $plan['limit'] ? number_format($plan['limit']).' Max' : '1,001+ Learners' }}
                        </span>
                    </div>
                    <p class="text-xs font-medium text-slate-500 leading-relaxed">
                        {{ $plan['description'] }}
                    </p>
                </div>
            </article>
        @endforeach
    </section>

    <!-- Section 2: School Licensing Management Forms -->
    <section class="space-y-4">
        <div class="flex items-center justify-between px-1">
            <h3 class="font-bold text-slate-900 text-base">Active School Subscriptions</h3>
            <span class="text-xs font-semibold text-slate-400">{{ count($schools) }} Total Enrolled</span>
        </div>

        @foreach($schools as $school)
            @php
                $limit = \App\Support\SubscriptionPlans::limit($school->license_plan);
                $suggested = \App\Support\SubscriptionPlans::suggestedFor($school->active_students_count);
            @endphp
            <form id="school-{{ $school->id }}" method="POST" action="{{ route('platform.licences.update', $school) }}" 
                  class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 transition-all hover:border-slate-300">
                @csrf 
                @method('PATCH')

                <!-- School Card Header Meta -->
                <div class="mb-5 flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-900 font-black text-sm flex items-center justify-center shrink-0 shadow-2xs">
                            {{ strtoupper(substr($school->name, 0, 1)) }}
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-base leading-tight">{{ $school->name }}</h3>
                            <div class="flex flex-wrap items-center gap-2 text-xs font-medium text-slate-400 mt-0.5">
                                <span class="font-mono text-slate-500">{{ $school->school_number }}</span>
                                <span>·</span>
                                <span class="font-semibold text-slate-700">{{ number_format($school->active_students_count) }} active learners</span>
                                @if($suggested !== $school->license_plan)
                                    <span>·</span>
                                    <span class="text-amber-700 bg-amber-50 border border-amber-200/60 px-2 py-0.5 rounded-md text-[10px] font-bold">
                                        Recommended: {{ ucfirst($suggested) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="self-start sm:self-auto">
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-3.5 py-1.5 text-xs font-bold text-white shadow-2xs">
                            <svg class="w-3.5 h-3.5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span>{{ $limit ? number_format($limit).' Capacity' : 'Unlimited Capacity' }}</span>
                        </span>
                    </div>
                </div>

                <!-- Input Controls Grid -->
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5 items-end">
                    <!-- Package Selection -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Package</label>
                        <div class="relative">
                            <select name="license_plan" 
                                    class="w-full text-xs font-bold bg-white border border-slate-200 rounded-xl pl-3.5 pr-8 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs appearance-none cursor-pointer">
                                @foreach(array_keys($plans) as $key)
                                    <option value="{{ $key }}" @selected($school->license_plan === $key)>{{ ucfirst($key) }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Status Selection -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Status</label>
                        <div class="relative">
                            <select name="license_status" 
                                    class="w-full text-xs font-bold bg-white border border-slate-200 rounded-xl pl-3.5 pr-8 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs appearance-none cursor-pointer">
                                @foreach(['active','trial','suspended','expired'] as $status)
                                    <option value="{{ $status }}" @selected($school->license_status === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Start Date -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Start Date</label>
                        <input type="date" name="license_started_at" value="{{ $school->license_started_at?->format('Y-m-d') }}" 
                               class="w-full text-xs font-bold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">
                    </div>

                    <!-- Expiration Date -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Expiration Date</label>
                        <input type="date" name="license_expires_at" value="{{ $school->license_expires_at?->format('Y-m-d') }}" 
                               class="w-full text-xs font-bold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">
                    </div>

                    <!-- Save Action Button -->
                    <div>
                        <button type="submit" 
                                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-amber-400 hover:bg-amber-300 active:scale-95 text-slate-950 font-black text-xs py-2.5 px-4 transition shadow-xs cursor-pointer h-[42px]">
                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Save Subscription</span>
                        </button>
                    </div>
                </div>
            </form>
        @endforeach
    </section>
</div>
@endsection