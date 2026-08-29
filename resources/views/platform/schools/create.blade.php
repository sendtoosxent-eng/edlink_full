@extends('layouts.platform', ['title' => 'Add school'])

@section('content')
<div class="space-y-6">
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 text-white shadow-sm sm:p-8">
        <div class="relative z-10 max-w-2xl"><span class="mb-3 inline-flex items-center gap-2 rounded-full border border-amber-400/20 bg-amber-400/10 px-3 py-1 text-xs font-bold text-amber-300"><span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>School onboarding</span><h1 class="text-2xl font-black tracking-tight text-amber-300 sm:text-3xl">Set up a new school</h1><p class="mt-1.5 text-sm font-medium leading-relaxed text-slate-400">Create the institution, its licence, and its first administrator in one secure transaction.</p></div>
        <div class="pointer-events-none absolute -bottom-20 -right-12 h-64 w-64 rounded-full bg-amber-500/10 blur-3xl"></div>
    </section>

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <p class="font-black">Please correct the following:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('platform.schools.store') }}" class="space-y-6">
        @csrf

        <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
            <p class="text-[10px] font-black uppercase tracking-wider text-amber-700">Step 1</p><h2 class="mt-1 text-lg font-black text-slate-950">School information</h2>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <label class="md:col-span-2"><span class="text-xs font-bold text-slate-700">School name</span><input name="name" value="{{ old('name') }}" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-amber-400 focus:bg-white focus:ring-amber-400" placeholder="e.g. Kampala Hills Secondary School"></label>
                <label><span class="text-xs font-bold text-slate-700">School type</span><select name="school_type" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-amber-400 focus:bg-white focus:ring-amber-400">@foreach(['kindergarten','primary','secondary','combined','tertiary'] as $type)<option value="{{ $type }}" @selected(old('school_type', 'secondary') === $type)>{{ ucfirst($type) }}</option>@endforeach</select></label>
                <label><span class="text-xs font-bold text-slate-700">School email</span><input type="email" name="email" value="{{ old('email') }}" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-amber-400 focus:bg-white focus:ring-amber-400" placeholder="office@school.test"></label>
                <label><span class="text-xs font-bold text-slate-700">Phone</span><input name="phone" value="{{ old('phone') }}" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-amber-400 focus:bg-white focus:ring-amber-400"></label>
                <label><span class="text-xs font-bold text-slate-700">Address</span><input name="address" value="{{ old('address') }}" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-amber-400 focus:bg-white focus:ring-amber-400"></label>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
            <p class="text-[10px] font-black uppercase tracking-wider text-amber-700">Step 2</p><h2 class="mt-1 text-lg font-black text-slate-950">Licence</h2>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <label><span class="text-xs font-bold text-slate-700">Package</span><select name="license_plan" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-amber-400 focus:bg-white focus:ring-amber-400">@foreach($plans as $key => $plan)<option value="{{ $key }}" @selected(old('license_plan', 'basic') === $key)>{{ $plan['name'] }}</option>@endforeach</select></label>
                <label><span class="text-xs font-bold text-slate-700">Status</span><select name="license_status" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-amber-400 focus:bg-white focus:ring-amber-400"><option value="active" @selected(old('license_status') === 'active')>Active customer</option><option value="trial" @selected(old('license_status', 'trial') === 'trial')>Trial / demo</option><option value="suspended" @selected(old('license_status') === 'suspended')>Suspended</option></select></label>
                <label><span class="text-xs font-bold text-slate-700">Starts</span><input type="date" name="license_started_at" value="{{ old('license_started_at', now()->toDateString()) }}" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-amber-400 focus:bg-white focus:ring-amber-400"></label>
                <label><span class="text-xs font-bold text-slate-700">Expires</span><input type="date" name="license_expires_at" value="{{ old('license_expires_at') }}" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-amber-400 focus:bg-white focus:ring-amber-400"><span class="mt-1 block text-xs text-slate-500">Required for trials; optional for active customers.</span></label>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
            <p class="text-[10px] font-black uppercase tracking-wider text-amber-700">Step 3</p><h2 class="mt-1 text-lg font-black text-slate-950">First school administrator</h2>
            <p class="mt-1 text-sm text-slate-500">Share these credentials securely. The administrator receives full school-level access.</p>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <label><span class="text-xs font-bold text-slate-700">Administrator name</span><input name="admin_name" value="{{ old('admin_name') }}" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-amber-400 focus:bg-white focus:ring-amber-400"></label>
                <label><span class="text-xs font-bold text-slate-700">Login email</span><input type="email" name="admin_email" value="{{ old('admin_email') }}" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-amber-400 focus:bg-white focus:ring-amber-400"></label>
                <label><span class="text-xs font-bold text-slate-700">Temporary password</span><input type="password" name="admin_password" required minlength="8" autocomplete="new-password" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-amber-400 focus:bg-white focus:ring-amber-400"></label>
                <label><span class="text-xs font-bold text-slate-700">Confirm password</span><input type="password" name="admin_password_confirmation" required minlength="8" autocomplete="new-password" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-amber-400 focus:bg-white focus:ring-amber-400"></label>
            </div>
        </section>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('platform.schools') }}" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700">Cancel</a>
            <button class="rounded-xl bg-amber-400 px-6 py-3 text-sm font-black text-slate-950 hover:bg-amber-300">Create school and administrator</button>
        </div>
    </form>
</div>
@endsection
