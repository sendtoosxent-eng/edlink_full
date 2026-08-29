@extends('layouts.platform', ['title' => 'School Groups'])

@section('content')
<div class="space-y-6">
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 text-white shadow-sm sm:p-8">
        <div class="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between"><div class="max-w-2xl"><span class="mb-3 inline-flex items-center gap-2 rounded-full border border-amber-400/20 bg-amber-400/10 px-3 py-1 text-xs font-bold text-amber-300"><span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>Multi-school management</span><h1 class="text-2xl font-black tracking-tight text-amber-300 sm:text-3xl">School groups & branches</h1><p class="mt-1.5 text-sm font-medium leading-relaxed text-slate-400">Connect related school tenants for group oversight while preserving independent operations and licensing.</p></div><a href="{{ route('platform.schools') }}" class="inline-flex items-center justify-center rounded-xl border border-white/15 bg-white/10 px-4 py-2.5 text-xs font-black text-white hover:bg-white/15">View all schools <span class="ml-2">→</span></a></div>
        <div class="pointer-events-none absolute -bottom-20 -right-12 h-64 w-64 rounded-full bg-amber-500/10 blur-3xl"></div>
    </section>

    @if(session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800">{{ $errors->first() }}</div>@endif

    <section class="grid gap-4 sm:grid-cols-3">
        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs"><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Registered groups</p><p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($groups->count()) }}</p><p class="mt-3 text-[11px] font-semibold text-slate-500">Multi-branch organisations</p></article>
        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs"><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Grouped branches</p><p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($groups->sum('schools_count')) }}</p><p class="mt-3 text-[11px] font-semibold text-slate-500">Schools connected to groups</p></article>
        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs"><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Available schools</p><p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($availableSchools->count()) }}</p><p class="mt-3 text-[11px] font-semibold text-slate-500">Not assigned to a group</p></article>
    </section>

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_390px]">
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
            <div class="border-b border-slate-100 p-5"><h2 class="text-base font-black text-slate-900">Registered groups</h2><p class="mt-1 text-xs text-slate-500">Open a group to manage branches, staff access, and consolidated reporting.</p></div>
            <div class="divide-y divide-slate-100">
                @forelse($groups as $group)
                    <a href="{{ route('platform.groups.show', $group) }}" class="flex items-center justify-between gap-4 p-5 transition hover:bg-slate-50/70"><div class="flex min-w-0 items-center gap-3"><span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-xs font-black text-amber-300">{{ strtoupper(substr($group->code, 0, 2)) }}</span><div class="min-w-0"><p class="truncate text-sm font-bold text-slate-900">{{ $group->name }}</p><p class="mt-1 truncate text-[10px] font-semibold text-slate-400">{{ $group->code }} · {{ $group->schools->pluck('name')->join(', ') ?: 'No branches assigned' }}</p></div></div><span class="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-black text-amber-800 ring-1 ring-inset ring-amber-200">{{ $group->schools_count }} {{ Str::plural('branch', $group->schools_count) }}</span></a>
                @empty
                    <div class="px-6 py-16 text-center"><p class="text-sm font-bold text-slate-700">No school groups yet</p><p class="mt-1 text-xs text-slate-400">Create the first group using the form.</p></div>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/80 bg-white shadow-xs">
            <div class="border-b border-slate-100 p-5"><p class="text-[10px] font-black uppercase tracking-wider text-amber-700">New organisation</p><h2 class="mt-1 text-lg font-black text-slate-900">Create school group</h2><p class="mt-1 text-xs leading-5 text-slate-500">Choose at least two currently ungrouped schools.</p></div>
            <form method="POST" action="{{ route('platform.groups.store') }}" class="space-y-4 p-5">@csrf
                <label class="block"><span class="text-xs font-bold text-slate-700">Group name</span><input name="name" value="{{ old('name') }}" required placeholder="e.g. Bright Future Schools" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold placeholder:text-slate-400 focus:border-amber-400 focus:bg-white focus:ring-amber-400"></label>
                <label class="block"><span class="text-xs font-bold text-slate-700">Group code</span><input name="code" value="{{ old('code') }}" required placeholder="BFS" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 font-mono text-sm font-bold uppercase placeholder:text-slate-400 focus:border-amber-400 focus:bg-white focus:ring-amber-400"><span class="mt-1.5 block text-[10px] text-slate-400">Letters, numbers, dashes, and underscores only.</span></label>
                <fieldset><legend class="text-xs font-bold text-slate-700">Founding branches</legend><div class="mt-2 max-h-56 space-y-2 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-3">@forelse($availableSchools as $school)<label class="flex cursor-pointer items-start gap-3 rounded-lg bg-white p-3 ring-1 ring-slate-200 transition hover:ring-amber-300"><input type="checkbox" name="school_ids[]" value="{{ $school->id }}" @checked(in_array($school->id, old('school_ids', []))) class="mt-0.5 rounded border-slate-300 text-amber-500 focus:ring-amber-400"><span class="min-w-0"><b class="block truncate text-xs text-slate-800">{{ $school->name }}</b><span class="mt-0.5 block font-mono text-[10px] text-slate-400">{{ $school->school_number }}</span></span></label>@empty<p class="p-3 text-center text-xs text-slate-400">No ungrouped schools are available.</p>@endforelse</div></fieldset>
                <button @disabled($availableSchools->count() < 2) class="w-full rounded-xl bg-amber-400 px-5 py-3 text-xs font-black text-slate-950 transition hover:bg-amber-300 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500">Create school group</button>
            </form>
        </section>
    </div>
</div>
@endsection
