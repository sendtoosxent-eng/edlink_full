@extends('layouts.platform', ['title' => 'Billing & Renewals'])

@section('content')
@php
    $today = now()->startOfDay();
    $activeCount = $schools->where('license_status', 'active')->count();
    $trialCount = $schools->where('license_status', 'trial')->count();
    $expiredCount = $schools->filter(fn ($school) => $school->license_status === 'expired' || ($school->license_expires_at && $school->license_expires_at->lt($today)))->count();
    $renewalCount = $schools->filter(fn ($school) => $school->license_expires_at && $school->license_expires_at->between($today, $today->copy()->addDays(30)))->count();
@endphp

<div class="space-y-6">
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 text-white shadow-sm sm:p-8">
        <div class="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <span class="mb-3 inline-flex items-center gap-2 rounded-full border border-amber-400/20 bg-amber-400/10 px-3 py-1 text-xs font-bold text-amber-300"><span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>Revenue operations</span>
                <h1 class="text-2xl font-black tracking-tight text-amber-300 sm:text-3xl">Billing & renewal control</h1>
                <p class="mt-1.5 text-sm font-medium leading-relaxed text-slate-400">Monitor subscription health, identify upcoming renewals, and keep every school on the right licence plan.</p>
            </div>
            <a href="{{ route('platform.licences') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-400 px-4 py-2.5 text-xs font-black text-slate-950 transition hover:bg-amber-300">Manage licences <span aria-hidden="true">→</span></a>
        </div>
        <div class="pointer-events-none absolute -bottom-20 -right-12 h-64 w-64 rounded-full bg-amber-500/10 blur-3xl"></div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([['Active subscriptions', $activeCount, 'Currently in good standing', 'emerald'], ['Renewing in 30 days', $renewalCount, 'Require follow-up soon', 'amber'], ['Trial accounts', $trialCount, 'Conversion opportunities', 'sky'], ['Expired / overdue', $expiredCount, 'Require immediate review', 'rose']] as [$label, $value, $note, $tone])
            @php($styles = ['emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'amber' => 'bg-amber-50 text-amber-800 border-amber-200', 'sky' => 'bg-sky-50 text-sky-700 border-sky-200', 'rose' => 'bg-rose-50 text-rose-700 border-rose-200'][$tone])
            <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <div class="flex items-start justify-between gap-3"><div><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ $label }}</p><p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($value) }}</p></div><span class="flex h-10 w-10 items-center justify-center rounded-xl border {{ $styles }}"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span></div>
                <p class="mt-4 text-[11px] font-semibold text-slate-500">{{ $note }}</p>
            </article>
        @endforeach
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
        <div class="flex flex-col gap-2 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-base font-bold text-slate-900">Renewal schedule</h2><p class="mt-0.5 text-xs font-medium text-slate-500">Ordered by the next licence expiry date.</p></div><span class="rounded-lg bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-slate-600">{{ $schools->count() }} schools</span></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left">
                <thead class="bg-slate-50/80"><tr class="text-[10px] font-black uppercase tracking-wider text-slate-500"><th class="px-5 py-3">School</th><th class="px-5 py-3">Plan</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Renewal date</th><th class="px-5 py-3">Timeline</th><th class="px-5 py-3 text-right">Action</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($schools as $school)
                        @php
                            $expiry = $school->license_expires_at;
                            $days = $expiry ? $today->diffInDays($expiry, false) : null;
                            $statusStyle = match($school->license_status) {'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'trial' => 'bg-sky-50 text-sky-700 ring-sky-200', 'suspended' => 'bg-amber-50 text-amber-800 ring-amber-200', default => 'bg-rose-50 text-rose-700 ring-rose-200'};
                        @endphp
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-5 py-4"><div class="flex items-center gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-xs font-black text-amber-300">{{ str($school->name)->substr(0, 1)->upper() }}</span><div><p class="text-sm font-bold text-slate-900">{{ $school->name }}</p><p class="mt-0.5 font-mono text-[10px] text-slate-400">{{ $school->school_number }}</p></div></div></td>
                            <td class="px-5 py-4 text-xs font-bold capitalize text-slate-700">{{ $school->license_plan ?: 'Unassigned' }}</td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide ring-1 ring-inset {{ $statusStyle }}">{{ $school->license_status ?: 'Unknown' }}</span></td>
                            <td class="whitespace-nowrap px-5 py-4"><p class="text-xs font-bold text-slate-700">{{ $expiry?->format('d M Y') ?? 'Not scheduled' }}</p><p class="mt-0.5 text-[10px] text-slate-400">Started {{ $school->license_started_at?->format('d M Y') ?? '—' }}</p></td>
                            <td class="whitespace-nowrap px-5 py-4">@if(is_null($days)) <span class="text-xs font-semibold text-slate-400">No expiry date</span>@elseif($days < 0) <span class="text-xs font-bold text-rose-600">{{ abs((int) $days) }} days overdue</span>@elseif($days === 0) <span class="text-xs font-bold text-rose-600">Due today</span>@elseif($days <= 30) <span class="text-xs font-bold text-amber-700">{{ (int) $days }} days remaining</span>@else <span class="text-xs font-semibold text-emerald-700">{{ (int) $days }} days remaining</span>@endif</td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('platform.schools.show', $school) }}" class="inline-flex items-center gap-1 text-xs font-black text-slate-700 hover:text-amber-700">View school <span aria-hidden="true">→</span></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-16 text-center"><p class="text-sm font-bold text-slate-700">No billing records yet</p><p class="mt-1 text-xs text-slate-400">Registered schools will appear here with their renewal status.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
