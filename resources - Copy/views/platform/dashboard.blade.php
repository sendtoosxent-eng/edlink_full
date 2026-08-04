@extends('layouts.platform', ['title' => 'Platform Dashboard'])

@section('content')
<div class="space-y-6 pb-12">
    <!-- Dark Gradient Hero Banner with Glow -->
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-sm">
        <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-400/10 border border-amber-400/20 text-amber-300 text-xs font-bold mb-3">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                    <span>Edlink Operations</span>
                </span>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-amber-300">
                    Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ str($admin->name)->before(' ') }}.
                </h1>
                <p class="mt-1.5 text-sm font-medium text-slate-400 leading-relaxed">
                    Here is the current position of schools, active licenses, learner usage, and system health across the platform.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="rounded-xl border border-slate-700/80 bg-slate-800/50 px-4 py-2.5 backdrop-blur-md">
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Today</span>
                    <b class="mt-0.5 block text-xs font-extrabold text-slate-100">{{ now()->format('D, d M Y') }}</b>
                </div>
                <button type="button" title="School registration will be added next" 
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-400 hover:bg-amber-300 active:scale-95 text-slate-950 font-black text-xs px-5 py-3 transition shadow-md hover:shadow-lg shrink-0 cursor-pointer">
                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <a href='/platform/schools/create'>Add School</a>
                </button>
            </div>
        </div>

        <!-- Ambient Lighting Effects -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute left-1/3 top-0 w-32 h-32 bg-slate-700/20 rounded-full blur-2xl pointer-events-none"></div>
    </section>

    <!-- Top Key Metrics Cards -->
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['All Schools', $schools->count(), 'Registered organizations', 'bg-slate-900', 'text-white', 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h5m-5 0V11m0 0h5m-5 0H7'],
            ['Active Licenses', $activeSchools->count(), $schools->count() ? round($activeSchools->count()/$schools->count()*100).'% of schools' : 'No schools', 'bg-amber-400', 'text-slate-950', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['Trial Schools', $trialSchools->count(), 'Require conversion review', 'bg-white', 'text-slate-900', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['Expired / Overdue', $expiredSchools->count(), $expiringSchools->count().' expiring in 30 days', 'bg-white', 'text-slate-900', 'M12 9v2m0 4h.01M5.1 19h13.8c1.5 0 2.4-1.6 1.7-2.9L13.7 4c-.8-1.3-2.6-1.3-3.4 0L3.4 16.1C2.7 17.4 3.6 19 5.1 19z']
        ] as [$label, $value, $note, $bg, $text, $icon])
            <article class="rounded-2xl border border-slate-200/80 {{ $bg }} {{ $text }} p-5 shadow-xs flex flex-col justify-between transition hover:border-slate-300">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-wider {{ $bg==='bg-white' ? 'text-slate-400' : ($bg==='bg-amber-400' ? 'text-slate-800' : 'text-slate-400') }}">
                            {{ $label }}
                        </p>
                        <p class="mt-2 text-3xl font-black tracking-tight">{{ number_format($value) }}</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl shrink-0 {{ $bg==='bg-white' ? 'bg-slate-100 text-slate-700' : ($bg==='bg-amber-400' ? 'bg-amber-500/20 text-slate-950' : 'bg-white/10 text-amber-300') }}">
                        <svg class="h-5 w-5 stroke-[2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-4 text-[11px] font-semibold {{ $bg==='bg-white' ? 'text-slate-500' : ($bg==='bg-amber-400' ? 'text-slate-800' : 'text-slate-400') }}">
                    {{ $note }}
                </p>
            </article>
        @endforeach
    </section>

    <!-- Usage Statistics Banner -->
    <section class="grid gap-4 sm:grid-cols-2">
        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 border border-amber-200/60 text-amber-800 shrink-0">
                    <svg class="h-6 w-6 stroke-[2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                    </svg>
                </span>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Learners Across Edlink</p>
                    <p class="mt-0.5 text-2xl font-black text-slate-900">{{ number_format($totalStudents) }}</p>
                </div>
            </div>
            <span class="rounded-full bg-amber-50 border border-amber-200/60 px-3 py-1 text-[10px] font-bold text-amber-900">
                Live Usage
            </span>
        </article>

        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 border border-slate-200/60 text-slate-800 shrink-0">
                    <svg class="h-6 w-6 stroke-[2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </span>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">School Staff Accounts</p>
                    <p class="mt-0.5 text-2xl font-black text-slate-900">{{ number_format($totalStaff) }}</p>
                </div>
            </div>
            <span class="rounded-full bg-slate-100 border border-slate-200/60 px-3 py-1 text-[10px] font-bold text-slate-700">
                All Schools
            </span>
        </article>
    </section>

    <!-- Subscription Growth SVG Chart -->
    <section class="rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-xs space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between border-b border-slate-100 pb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Subscription Growth Trends</h3>
                <p class="mt-0.5 text-xs font-medium text-slate-500">Basic, Premium, and Enterprise plan distribution over the last six months</p>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-xs font-bold">
                <span class="inline-flex items-center gap-1.5 text-slate-700">
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>Basic
                </span>
                <span class="inline-flex items-center gap-1.5 text-slate-700">
                    <span class="h-2.5 w-2.5 rounded-full bg-slate-900"></span>Premium
                </span>
                <span class="inline-flex items-center gap-1.5 text-slate-700">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>Enterprise
                </span>
            </div>
        </div>

        @php($seriesMax = max(1, $subscriptionSeries->flatten()->max()))
        <div class="overflow-x-auto pt-2">
            <svg viewBox="0 0 520 210" class="h-64 min-w-[520px] w-full" role="img" aria-label="Subscription growth line chart">
                <!-- Grid Lines -->
                @foreach([30,65,100,135,170] as $y)
                    <line x1="20" y1="{{ $y }}" x2="500" y2="{{ $y }}" stroke="#f1f5f9" stroke-width="1"/>
                @endforeach

                <!-- Month Labels -->
                @foreach($registrationLabels as $index => $label)
                    <text x="{{ 20 + $index * 96 }}" y="200" font-size="10" font-weight="600" fill="#94a3b8" text-anchor="middle">{{ $label }}</text>
                @endforeach

                <!-- Series Lines -->
                @foreach(['basic' => '#fbbf24', 'premium' => '#0f172a', 'enterprise' => '#10b981'] as $plan => $colour)
                    @php($points = $subscriptionSeries[$plan]->map(fn($value, $index) => (20 + $index * 96) . ',' . round(170 - ($value / $seriesMax) * 135))->implode(' '))
                    <polyline points="{{ $points }}" fill="none" stroke="{{ $colour }}" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
                    @foreach($subscriptionSeries[$plan] as $index => $value)
                        <circle cx="{{ 20 + $index * 96 }}" cy="{{ round(170 - ($value / $seriesMax) * 135) }}" r="4" fill="white" stroke="{{ $colour }}" stroke-width="2.5">
                            <title>{{ ucfirst($plan) }}: {{ $value }}</title>
                        </circle>
                    @endforeach
                @endforeach
            </svg>
        </div>
    </section>

    <!-- Registration Bar Chart & License Distribution Cards -->
    <div class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
        <!-- Registration Bar Chart -->
        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">School Registrations</h3>
                        <p class="mt-0.5 text-xs font-medium text-slate-500">New tenant onboarding momentum</p>
                    </div>
                    <span class="rounded-lg bg-slate-100 border border-slate-200/60 px-2.5 py-1 text-[10px] font-bold text-slate-600">6 Months</span>
                </div>

                @php($maxRegistration = max(1, $registrationData->max()))
                <div class="mt-6 flex h-48 items-end gap-3 sm:gap-5">
                    @foreach($registrationData as $index => $value)
                        <div class="flex h-full flex-1 flex-col justify-end gap-2">
                            <div class="group relative flex h-full items-end rounded-xl bg-slate-50 p-1">
                                <div class="w-full rounded-lg bg-slate-900 transition-all duration-200 group-hover:bg-amber-400" 
                                     style="height:{{ max(6, ($value / $maxRegistration) * 100) }}%"></div>
                                <span class="absolute -top-7 left-1/2 hidden -translate-x-1/2 rounded-md bg-slate-900 px-2 py-0.5 text-[10px] font-bold text-white group-hover:block shadow-xs">
                                    {{ $value }}
                                </span>
                            </div>
                            <span class="text-center text-[10px] font-bold text-slate-400">{{ $registrationLabels[$index] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- License Plans Breakdown -->
        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-xs flex flex-col justify-between">
            <div>
                <div class="border-b border-slate-100 pb-4">
                    <h3 class="text-base font-bold text-slate-900">License Plans Breakdown</h3>
                    <p class="mt-0.5 text-xs font-medium text-slate-500">Current active tier distribution</p>
                </div>

                <div class="mt-5 space-y-4">
                    @forelse($planCounts as $plan => $count)
                        @php($percentage = $schools->count() ? round(($count / $schools->count()) * 100) : 0)
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-xs font-bold">
                                <span class="capitalize text-slate-800">{{ $plan ?: 'Unassigned' }}</span>
                                <span class="text-slate-500 font-mono">{{ $count }} · {{ $percentage }}%</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100 border border-slate-200/60 p-0.5">
                                <div class="h-full rounded-full transition-all duration-300 {{ $loop->first ? 'bg-amber-400' : 'bg-slate-900' }}" 
                                     style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-xl bg-slate-50 p-6 text-center text-xs font-medium text-slate-400">No license data recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    <!-- Recently Registered Schools & System Security Logs -->
    <div class="grid gap-6 xl:grid-cols-[1.55fr_1fr]">
        <!-- Recent Schools Table -->
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
            <div class="flex items-center justify-between border-b border-slate-100 p-5">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Recently Registered Schools</h3>
                    <p class="mt-0.5 text-xs font-medium text-slate-500">Latest organizations joining Edlink</p>
                </div>
                <a href="{{ route('platform.licences') }}" class="inline-flex items-center gap-1 text-xs font-bold text-slate-900 hover:text-amber-600 transition">
                    <span>View All</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[600px] text-left text-xs">
                    <thead class="bg-slate-900 text-white font-bold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="px-5 py-3.5">School Name</th>
                            <th class="px-4 py-3.5">Plan</th>
                            <th class="px-4 py-3.5">Active Learners</th>
                            <th class="px-4 py-3.5">Status</th>
                            <th class="px-5 py-3.5">Joined</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
                        @forelse($recentSchools as $school)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-lg font-black text-amber-900 bg-amber-100 border border-amber-200/80 text-xs shrink-0">
                                            {{ strtoupper(substr($school->name, 0, 1)) }}
                                        </span>
                                        <div class="min-w-0">
                                            <b class="block truncate font-bold text-slate-900 text-xs">{{ $school->name }}</b>
                                            <span class="text-[10px] font-mono text-slate-400">{{ $school->school_number }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 capitalize font-bold text-slate-700">
                                    {{ $school->license_plan }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <b class="text-slate-900">{{ $school->students_count }}</b>
                                    <span class="block text-[10px] text-slate-400">{{ $school->users_count }} accounts</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    @if($school->license_status === 'active')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-[10px] font-bold capitalize">
                                            {{ $school->license_status }}
                                        </span>
                                    @elseif($school->license_status === 'trial')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-[10px] font-bold capitalize">
                                            {{ $school->license_status }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-rose-50 border border-rose-200 text-rose-800 text-[10px] font-bold capitalize">
                                            {{ $school->license_status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-slate-500 font-mono text-[11px]">
                                    {{ $school->created_at?->format('d M Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-xs font-medium text-slate-400">
                                    No registered schools recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Security Activity Stream -->
        <section class="rounded-2xl border border-slate-200/80 bg-white shadow-xs flex flex-col justify-between">
            <div>
                <div class="border-b border-slate-100 p-5">
                    <h3 class="text-base font-bold text-slate-900">Platform Security Logs</h3>
                    <p class="mt-0.5 text-xs font-medium text-slate-500">Authentication & owner events</p>
                </div>

                <div class="divide-y divide-slate-100 px-5">
                    @forelse($platformLogs as $log)
                        <div class="flex items-start gap-3 py-3.5">
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full {{ str_contains($log->event, 'failed') ? 'bg-rose-500' : (str_contains($log->event, 'succeeded') || str_contains($log->event, 'enabled') ? 'bg-emerald-500' : 'bg-amber-400') }}"></span>
                            <div class="min-w-0">
                                <b class="block truncate text-xs font-bold text-slate-800">
                                    {{ str($log->event)->replace(['platform.', '.'], ' ')->title() }}
                                </b>
                                <p class="mt-0.5 text-[10px] font-medium text-slate-400">
                                    {{ $log->administrator?->name ?: 'Unknown Admin' }} · {{ $log->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="py-10 text-center text-xs font-medium text-slate-400">No platform activity logged.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    <!-- Expiring Licenses Alert Banner -->
    @if($expiringSchools->isNotEmpty())
        <section class="rounded-2xl border border-amber-300/80 bg-amber-50/70 p-5 shadow-xs">
            <div class="flex items-start gap-4">
                <div class="p-2 bg-amber-100 text-amber-900 rounded-xl shrink-0">
                    <svg class="w-5 h-5 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-amber-950">Licenses Requiring Immediate Attention</h3>
                    <p class="mt-0.5 text-xs font-medium text-amber-800">
                        {{ $expiringSchools->count() }} school license(s) expire within the next 30 days.
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($expiringSchools->take(5) as $school)
                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-white px-3 py-1.5 text-[11px] font-bold text-amber-900 shadow-2xs">
                                <span>{{ $school->name }}</span>
                                <span class="text-amber-600 font-normal">({{ $school->license_expires_at->diffForHumans() }})</span>
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>
@endsection
