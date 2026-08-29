@extends('layouts.platform', ['title' => 'System Settings'])

@section('content')
<div class="space-y-6">
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 text-white shadow-sm sm:p-8">
        <div class="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl"><span class="mb-3 inline-flex items-center gap-2 rounded-full border border-amber-400/20 bg-amber-400/10 px-3 py-1 text-xs font-bold text-amber-300"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>Platform configuration</span><h1 class="text-2xl font-black tracking-tight text-amber-300 sm:text-3xl">Operations & system health</h1><p class="mt-1.5 text-sm font-medium leading-relaxed text-slate-400">Monitor service readiness and manage the operational defaults used across Edlink.</p></div>
            <a href="{{ route('platform.backups') }}" class="inline-flex items-center justify-center rounded-xl border border-white/15 bg-white/10 px-4 py-2.5 text-xs font-black text-white transition hover:bg-white/15">View backup centre <span class="ml-2">→</span></a>
        </div>
        <div class="pointer-events-none absolute -bottom-20 -right-12 h-64 w-64 rounded-full bg-amber-500/10 blur-3xl"></div>
    </section>

    @if(session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800">{{ $errors->first() }}</div>@endif

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($health as $label => $value)
            @php
                $problem = in_array($label, ['Failed jobs', 'Pending jobs']) ? (int) $value > 0 : in_array($value, ['Unavailable', 'Never', 'Not configured'], true);
            @endphp
            <article class="rounded-2xl border bg-white p-5 shadow-xs {{ $problem ? 'border-rose-200' : 'border-slate-200/80' }}"><div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ $label }}</p><p class="mt-2 truncate text-sm font-black {{ $problem ? 'text-rose-700' : 'text-slate-900' }}">{{ $value }}</p></div><span class="mt-0.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $problem ? 'bg-rose-500' : 'bg-emerald-500' }}"></span></div></article>
        @endforeach
    </section>

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
        <form method="POST" action="{{ route('platform.settings.update') }}" class="rounded-2xl border border-slate-200/80 bg-white shadow-xs">
            @csrf @method('PUT')
            <div class="border-b border-slate-100 p-5 sm:p-6"><p class="text-[10px] font-black uppercase tracking-wider text-amber-700">Operational defaults</p><h2 class="mt-1 text-lg font-black text-slate-900">Platform settings</h2><p class="mt-1 text-xs leading-5 text-slate-500">These values control support routing, renewal alerts, and maintenance communication.</p></div>
            <div class="space-y-5 p-5 sm:p-6">
                <label class="block"><span class="text-xs font-bold text-slate-700">Support email</span><input type="email" name="support_email" value="{{ old('support_email', $settings['support_email']) }}" required placeholder="support@edlink.space" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 placeholder:text-slate-400 focus:border-amber-400 focus:bg-white focus:ring-amber-400"><span class="mt-1.5 block text-[10px] text-slate-400">Operational notices and customer support communication use this address.</span></label>
                <label class="block"><span class="text-xs font-bold text-slate-700">Renewal warning period</span><div class="relative mt-2"><input type="number" min="1" max="180" name="renewal_warning_days" value="{{ old('renewal_warning_days', $settings['renewal_warning_days']) }}" required class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 pr-16 text-sm font-semibold text-slate-800 focus:border-amber-400 focus:bg-white focus:ring-amber-400"><span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-xs font-bold text-slate-400">days</span></div></label>
                <label class="block"><span class="text-xs font-bold text-slate-700">Maintenance message</span><textarea name="maintenance_message" rows="5" maxlength="500" placeholder="Optional message displayed during planned maintenance…" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium leading-6 text-slate-800 placeholder:text-slate-400 focus:border-amber-400 focus:bg-white focus:ring-amber-400">{{ old('maintenance_message', $settings['maintenance_message']) }}</textarea><span class="mt-1.5 block text-[10px] text-slate-400">Maximum 500 characters. Leave blank when no maintenance notice is required.</span></label>
            </div>
            <div class="flex justify-end border-t border-slate-100 bg-slate-50/70 p-5 sm:px-6"><button class="rounded-xl bg-amber-400 px-6 py-3 text-xs font-black text-slate-950 transition hover:bg-amber-300">Save platform settings</button></div>
        </form>

        <aside class="space-y-4">
            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs"><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Recovery snapshot</p>@if($lastBackup)<p class="mt-3 text-sm font-black text-slate-900">{{ basename($lastBackup->path) }}</p><div class="mt-3 grid grid-cols-2 gap-3 text-xs"><div class="rounded-xl bg-slate-50 p-3"><span class="block text-[9px] font-black uppercase text-slate-400">Size</span><b class="mt-1 block text-slate-800">{{ number_format($lastBackup->size / 1048576, 2) }} MB</b></div><div class="rounded-xl bg-slate-50 p-3"><span class="block text-[9px] font-black uppercase text-slate-400">Status</span><b class="mt-1 block capitalize text-emerald-700">{{ $lastBackup->status }}</b></div></div>@else<p class="mt-3 text-sm font-bold text-amber-800">No verified backup recorded</p><p class="mt-1 text-xs leading-5 text-slate-500">Check the scheduler and backup centre before making major system changes.</p>@endif</section>
            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5"><p class="text-xs font-black text-amber-900">Configuration safety</p><p class="mt-2 text-xs leading-5 text-amber-800">SMTP credentials, database secrets, and API keys remain server environment values and are intentionally not editable here.</p></section>
        </aside>
    </div>
</div>
@endsection
