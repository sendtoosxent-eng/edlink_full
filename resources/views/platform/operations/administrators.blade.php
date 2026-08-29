@extends('layouts.platform', ['title' => 'Platform Administrators'])

@section('content')
<div class="space-y-6">
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 text-white shadow-sm sm:p-8">
        <div class="relative z-10 max-w-2xl"><span class="mb-3 inline-flex items-center gap-2 rounded-full border border-amber-400/20 bg-amber-400/10 px-3 py-1 text-xs font-bold text-amber-300"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>Privileged access</span><h1 class="text-2xl font-black tracking-tight text-amber-300 sm:text-3xl">Platform administrators</h1><p class="mt-1.5 text-sm font-medium leading-relaxed text-slate-400">Control platform-wide roles, account status, and secure administrative access.</p></div>
        <div class="pointer-events-none absolute -bottom-20 -right-12 h-64 w-64 rounded-full bg-amber-500/10 blur-3xl"></div>
    </section>

    @if(session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800">{{ $errors->first() }}</div>@endif

    <section class="grid gap-4 sm:grid-cols-3">
        @foreach([['Total administrators', $admins->count(), 'All privileged accounts'], ['Active accounts', $admins->where('is_active', true)->count(), 'Allowed to authenticate'], ['Platform owners', $admins->where('role', 'platform_owner')->count(), 'Highest access level']] as [$label, $value, $note])
            <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs"><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ $label }}</p><p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($value) }}</p><p class="mt-3 text-[11px] font-semibold text-slate-500">{{ $note }}</p></article>
        @endforeach
    </section>

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
            <div class="border-b border-slate-100 p-5"><h2 class="text-base font-black text-slate-900">Administrator directory</h2><p class="mt-1 text-xs text-slate-500">Role and status updates take effect on the next request.</p></div>
            <div class="divide-y divide-slate-100">
                @forelse($admins as $admin)
                    <form method="POST" action="{{ route('platform.administrators.update', $admin) }}" class="grid gap-4 p-5 transition hover:bg-slate-50/70 lg:grid-cols-[minmax(180px,1fr)_200px_120px_auto] lg:items-center">
                        @csrf @method('PATCH')
                        <div class="flex min-w-0 items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-xs font-black text-amber-300">{{ strtoupper(substr($admin->name, 0, 2)) }}</span><div class="min-w-0"><p class="truncate text-sm font-bold text-slate-900">{{ $admin->name }}</p><p class="mt-0.5 truncate text-[10px] text-slate-400">{{ $admin->email }}</p></div></div>
                        <label><span class="mb-1 block text-[9px] font-black uppercase tracking-wider text-slate-400">Access role</span><select name="role" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-bold text-slate-700 focus:border-amber-400 focus:bg-white focus:ring-amber-400"><option value="operations_admin" @selected($admin->role === 'operations_admin')>Operations admin</option><option value="support_admin" @selected($admin->role === 'support_admin')>Support admin</option><option value="platform_owner" @selected($admin->role === 'platform_owner')>Platform owner</option></select></label>
                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($admin->is_active) class="rounded border-slate-300 text-amber-500 focus:ring-amber-400"><span class="text-xs font-bold text-slate-700">Active</span></label>
                        <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-black text-white transition hover:bg-slate-800">Save</button>
                    </form>
                @empty
                    <div class="px-6 py-16 text-center text-sm font-semibold text-slate-400">No platform administrators found.</div>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/80 bg-white shadow-xs">
            <div class="border-b border-slate-100 p-5"><p class="text-[10px] font-black uppercase tracking-wider text-amber-700">New account</p><h2 class="mt-1 text-lg font-black text-slate-900">Add administrator</h2><p class="mt-1 text-xs leading-5 text-slate-500">Create a privileged account with a temporary secure password.</p></div>
            <form method="POST" action="{{ route('platform.administrators.store') }}" class="space-y-4 p-5">@csrf
                <label class="block"><span class="text-xs font-bold text-slate-700">Full name</span><input name="name" value="{{ old('name') }}" required placeholder="e.g. Sarah Kato" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold placeholder:text-slate-400 focus:border-amber-400 focus:bg-white focus:ring-amber-400"></label>
                <label class="block"><span class="text-xs font-bold text-slate-700">Email address</span><input type="email" name="email" value="{{ old('email') }}" required placeholder="sarah@edlink.space" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold placeholder:text-slate-400 focus:border-amber-400 focus:bg-white focus:ring-amber-400"></label>
                <label class="block"><span class="text-xs font-bold text-slate-700">Access role</span><select name="role" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold focus:border-amber-400 focus:bg-white focus:ring-amber-400"><option value="operations_admin" @selected(old('role') === 'operations_admin')>Operations administrator</option><option value="support_admin" @selected(old('role') === 'support_admin')>Support administrator</option><option value="platform_owner" @selected(old('role') === 'platform_owner')>Platform owner</option></select></label>
                <div class="grid gap-4 sm:grid-cols-2"><label class="block"><span class="text-xs font-bold text-slate-700">Password</span><input type="password" name="password" required minlength="12" autocomplete="new-password" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-amber-400 focus:bg-white focus:ring-amber-400"></label><label class="block"><span class="text-xs font-bold text-slate-700">Confirm</span><input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-amber-400 focus:bg-white focus:ring-amber-400"></label></div>
                <p class="rounded-xl bg-amber-50 p-3 text-[10px] leading-5 text-amber-800 ring-1 ring-inset ring-amber-200">Use at least 12 characters. The administrator will enrol MFA after their first password sign-in.</p>
                <button class="w-full rounded-xl bg-amber-400 px-5 py-3 text-xs font-black text-slate-950 transition hover:bg-amber-300">Create administrator</button>
            </form>
        </section>
    </div>
</div>
@endsection
