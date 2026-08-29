@extends('layouts.platform', ['title' => 'Database Backups'])

@section('content')
@php
    $isHealthy = $latest?->status === 'verified' && $latest->verified_at?->gte(now()->subDays(2));
    $retentionDays = config('edlink.backup_retention_days', 30);
    $nextBackup = now()->setTime(1, 30);
    if ($nextBackup->isPast()) $nextBackup->addDay();
@endphp

<div class="space-y-6">
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 text-white shadow-sm sm:p-8">
        <div class="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <span class="mb-3 inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold {{ $isHealthy ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-300' : 'border-amber-400/20 bg-amber-400/10 text-amber-300' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $isHealthy ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                    {{ $isHealthy ? 'Recovery protection healthy' : 'Backup attention required' }}
                </span>
                <h1 class="text-2xl font-black tracking-tight text-amber-300 sm:text-3xl">Database backup centre</h1>
                <p class="mt-1.5 text-sm font-medium leading-relaxed text-slate-400">Track automated snapshots, checksum verification, restore tests, retention, and recovery readiness.</p>
            </div>
            <div class="rounded-xl border border-slate-700/80 bg-slate-800/60 px-4 py-3 backdrop-blur">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Next scheduled run</p>
                <p class="mt-1 text-xs font-bold text-white">{{ $nextBackup->format('D, d M · H:i') }}</p>
            </div>
        </div>
        <div class="pointer-events-none absolute -bottom-20 -right-12 h-64 w-64 rounded-full bg-amber-500/10 blur-3xl"></div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Latest backup', $latest?->created_at?->diffForHumans() ?? 'Never', $latest ? basename($latest->path) : 'No backup record exists', $isHealthy ? 'emerald' : 'rose'],
            ['Verified copies', number_format($verifiedCount), 'Checksum and dump integrity passed', 'emerald'],
            ['Failed attempts', number_format($failedCount), $failedCount ? 'Review failure details below' : 'No recorded failures', $failedCount ? 'rose' : 'slate'],
            ['Stored volume', number_format($totalSize / 1048576, 2).' MB', $retentionDays.'-day rolling retention', 'amber'],
        ] as [$label, $value, $note, $tone])
            @php
                $toneClass = [
                    'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                    'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
                    'amber' => 'bg-amber-50 text-amber-800 ring-amber-200',
                    'slate' => 'bg-slate-100 text-slate-700 ring-slate-200',
                ][$tone];
            @endphp
            <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ $label }}</p><p class="mt-2 truncate text-xl font-black text-slate-900">{{ $value }}</p></div><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ring-1 ring-inset {{ $toneClass }}"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7c0-2 3.6-3 8-3s8 1 8 3-3.6 3-8 3-8-1-8-3zm0 0v5c0 2 3.6 3 8 3s8-1 8-3V7m-16 5v5c0 2 3.6 3 8 3s8-1 8-3v-5"/></svg></span></div>
                <p class="mt-3 truncate text-[11px] font-semibold text-slate-500" title="{{ $note }}">{{ $note }}</p>
            </article>
        @endforeach
    </section>

    <section class="grid gap-4 lg:grid-cols-3">
        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs"><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Private storage</p><h2 class="mt-2 text-sm font-bold text-slate-900">Local server disk</h2><code class="mt-3 block break-all rounded-xl bg-slate-900 p-3 text-[11px] leading-5 text-slate-300">storage/app/private/backups</code><p class="mt-3 text-xs leading-5 text-slate-500">Files are outside the public web directory and are not downloadable without server access.</p></article>
        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs"><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Automation</p><h2 class="mt-2 text-sm font-bold text-slate-900">Daily at 01:30</h2><p class="mt-3 text-xs leading-5 text-slate-500">Laravel Scheduler runs a compressed database dump, verifies its SHA-256 checksum and dump header, then performs a restoration test.</p><span class="mt-3 inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-black text-amber-800 ring-1 ring-inset ring-amber-200">Scheduler cron required</span></article>
        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs"><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Retention policy</p><h2 class="mt-2 text-sm font-bold text-slate-900">Keep {{ $retentionDays }} days</h2><p class="mt-3 text-xs leading-5 text-slate-500">Expired files and their history records are pruned after each successful run. These backups remain on the same hosting account.</p><span class="mt-3 inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-[10px] font-black text-sky-700 ring-1 ring-inset ring-sky-200">Off-site copy recommended</span></article>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
        <div class="flex flex-col gap-2 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-base font-bold text-slate-900">Backup history</h2><p class="mt-0.5 text-xs font-medium text-slate-500">Verification and recovery evidence recorded by the automated job.</p></div><span class="rounded-lg bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-slate-600">{{ $backups->total() }} records</span></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left">
                <thead class="bg-slate-50/80"><tr class="text-[10px] font-black uppercase tracking-wider text-slate-500"><th class="px-5 py-3">Backup</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Size</th><th class="px-5 py-3">Integrity verified</th><th class="px-5 py-3">Restore tested</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($backups as $backup)
                        <tr class="align-top transition hover:bg-slate-50/70">
                            <td class="px-5 py-4"><p class="text-xs font-bold text-slate-800">{{ basename($backup->path) }}</p><p class="mt-1 text-[10px] text-slate-400">{{ $backup->created_at?->format('d M Y · H:i:s') }}</p>@if($backup->failure)<p class="mt-2 max-w-xl rounded-lg bg-rose-50 px-2.5 py-2 text-[10px] leading-4 text-rose-700">{{ $backup->failure }}</p>@endif</td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide ring-1 ring-inset {{ $backup->status === 'verified' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : ($backup->status === 'failed' ? 'bg-rose-50 text-rose-700 ring-rose-200' : 'bg-amber-50 text-amber-800 ring-amber-200') }}">{{ $backup->status }}</span></td>
                            <td class="whitespace-nowrap px-5 py-4 text-xs font-bold text-slate-700">{{ number_format($backup->size / 1048576, 2) }} MB</td>
                            <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-600">{{ $backup->verified_at?->format('d M Y, H:i') ?? 'Not verified' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-600">{{ $backup->restored_tested_at?->format('d M Y, H:i') ?? 'Not tested' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-16 text-center"><span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200"><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 4h.01M10.3 4.4 2.9 17.2A2 2 0 004.6 20h14.8a2 2 0 001.7-2.8L13.7 4.4a2 2 0 00-3.4 0z"/></svg></span><p class="mt-4 text-sm font-bold text-slate-700">No automated backup has run yet</p><p class="mt-1 text-xs text-slate-400">Confirm the server cron runs <code>php artisan schedule:run</code> every minute.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($backups->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $backups->links() }}</div>@endif
    </section>
</div>
@endsection
