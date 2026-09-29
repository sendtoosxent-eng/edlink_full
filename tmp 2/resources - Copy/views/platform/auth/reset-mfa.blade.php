@extends('layouts.platform-auth')

@section('content')
<div class="rounded-3xl border border-slate-200 bg-white p-7 shadow-xl shadow-slate-200/60 sm:p-9">
    <a href="{{ route('platform.challenge') }}" class="text-xs font-bold text-slate-400 hover:text-slate-700">← Back to verification</a>
    <div class="mt-5 flex items-start justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-amber-600">Account recovery</p>
            <h2 class="mt-2 text-2xl font-extrabold text-slate-900">Reset MFA</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">This invalidates your current authenticator connection and every remaining recovery code.</p>
        </div>
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-100 font-black text-amber-700">↻</span>
    </div>

    <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-800">
        After confirming, Edlink will display a new QR code. You must connect your authenticator and save the 10 new recovery codes.
    </div>

    <form method="POST" action="{{ route('platform.mfa.reset.store') }}" class="mt-6 space-y-5">
        @csrf
        <label class="block">
            <span class="text-xs font-bold text-slate-700">Platform password</span>
            <input type="password" name="password" required autocomplete="current-password" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-amber-400 focus:bg-white focus:ring-4 focus:ring-amber-400/10">
            @error('password')<span class="mt-2 block text-xs font-bold text-rose-600">{{ $message }}</span>@enderror
        </label>
        <label class="block">
            <span class="text-xs font-bold text-slate-700">Type <b>RESET MFA</b> to confirm</span>
            <input name="confirmation" required autocomplete="off" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-sm font-bold outline-none focus:border-amber-400 focus:bg-white focus:ring-4 focus:ring-amber-400/10">
            @error('confirmation')<span class="mt-2 block text-xs font-bold text-rose-600">{{ $message }}</span>@enderror
        </label>
        <button class="w-full rounded-xl bg-slate-950 px-5 py-3.5 text-sm font-bold text-white hover:bg-slate-800">Reset and reconnect authenticator</button>
    </form>
</div>
@endsection
