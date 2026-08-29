@extends('layouts.platform', ['title' => 'Platform Audit'])

@section('content')
@php
    $pageLogs = collect($logs->items());
    $todayCount = $pageLogs->filter(fn ($log) => $log->created_at?->isToday())->count();
    $adminCount = $pageLogs->pluck('platform_admin_id')->filter()->unique()->count();
    $systemCount = $pageLogs->whereNull('platform_admin_id')->count();
@endphp

<div class="space-y-6">
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 text-white shadow-sm sm:p-8">
        <div class="relative z-10 max-w-2xl">
            <span class="mb-3 inline-flex items-center gap-2 rounded-full border border-amber-400/20 bg-amber-400/10 px-3 py-1 text-xs font-bold text-amber-300"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>Security capture active</span>
            <h1 class="text-2xl font-black tracking-tight text-amber-300 sm:text-3xl">Platform audit trail</h1>
            <p class="mt-1.5 text-sm font-medium leading-relaxed text-slate-400">Review sensitive platform activity, trace administrator actions, and inspect the context retained with every event.</p>
        </div>
        <div class="pointer-events-none absolute -bottom-20 -right-12 h-64 w-64 rounded-full bg-amber-500/10 blur-3xl"></div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([['Total records', $logs->total(), 'Matching the current filter'], ['On this page', $pageLogs->count(), 'Up to '.$logs->perPage().' recent events'], ['Today', $todayCount, 'Events visible on this page'], ['Actors', $adminCount + ($systemCount ? 1 : 0), $systemCount ? $systemCount.' system event(s)' : 'Administrator activity']] as [$label, $value, $note])
            <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs"><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ $label }}</p><p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($value) }}</p><p class="mt-3 text-[11px] font-semibold text-slate-500">{{ $note }}</p></article>
        @endforeach
    </section>

    <section class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:p-5">
        <form method="GET" action="{{ route('platform.audit') }}" class="flex flex-col gap-3 sm:flex-row">
            <label class="relative flex-1"><span class="sr-only">Search audit events</span><svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 11-16 0 8 8 0 0116 0z"/></svg><input name="search" value="{{ $search }}" placeholder="Search by event name, for example: school.updated" class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-xs font-semibold text-slate-800 placeholder:text-slate-400 focus:border-amber-400 focus:ring-amber-400"></label>
            <button class="rounded-xl bg-slate-900 px-5 py-2.5 text-xs font-black text-white transition hover:bg-slate-800">Search records</button>
            @if($search)<a href="{{ route('platform.audit') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Clear</a>@endif
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
        <div class="flex items-center justify-between border-b border-slate-100 p-5"><div><h2 class="text-base font-bold text-slate-900">Recorded activity</h2><p class="mt-0.5 text-xs font-medium text-slate-500">Newest events appear first. Times use {{ config('app.timezone') }}.</p></div><span class="hidden rounded-lg bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-slate-600 sm:inline-flex">Immutable log</span></div>
        <div class="divide-y divide-slate-100">
            @forelse($logs as $log)
                @php
                    $eventLabel = str($log->event)->replace('.', ' ')->headline();
                    $metadata = is_array($log->metadata) ? $log->metadata : (array) $log->metadata;
                @endphp
                <article class="grid gap-4 p-5 transition hover:bg-slate-50/70 lg:grid-cols-[minmax(0,1.4fr)_minmax(180px,.7fr)_minmax(170px,.6fr)] lg:items-center">
                    <div class="flex min-w-0 items-start gap-3"><span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-200"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span><div class="min-w-0"><p class="truncate text-sm font-bold text-slate-900">{{ $eventLabel }}</p><p class="mt-0.5 truncate font-mono text-[10px] text-slate-400">{{ $log->event }}</p>@if(count($metadata))<details class="mt-2"><summary class="cursor-pointer text-[10px] font-black uppercase tracking-wider text-amber-700">View event details</summary><pre class="mt-2 max-h-40 overflow-auto whitespace-pre-wrap break-all rounded-xl bg-slate-900 p-3 text-[10px] leading-5 text-slate-300">{{ json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></details>@endif</div></div>
                    <div><p class="text-xs font-bold text-slate-800">{{ $log->administrator?->name ?? 'System process' }}</p><p class="mt-0.5 text-[10px] text-slate-400">{{ $log->administrator?->email ?? ($log->ip_address ?: 'No actor recorded') }}</p></div>
                    <div class="lg:text-right"><p class="text-xs font-bold text-slate-700">{{ $log->created_at?->format('d M Y') }}</p><p class="mt-0.5 text-[10px] font-semibold text-slate-400">{{ $log->created_at?->format('H:i:s') }} · {{ $log->created_at?->diffForHumans() }}</p></div>
                </article>
            @empty
                <div class="px-6 py-16 text-center"><span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.172 9.172a4 4 0 015.656 5.656M3 3l18 18"/></svg></span><p class="mt-4 text-sm font-bold text-slate-700">No audit activity found</p><p class="mt-1 text-xs text-slate-400">Try a different event name or clear the current search.</p></div>
            @endforelse
        </div>
        @if($logs->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $logs->links() }}</div>@endif
    </section>
</div>
@endsection
