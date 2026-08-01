@extends('layouts.platform', ['title' => 'Add school'])

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <p class="text-xs font-black uppercase tracking-[0.22em] text-amber-600">School onboarding</p>
        <h1 class="mt-2 text-3xl font-black text-slate-950">Set up a new school</h1>
        <p class="mt-2 text-sm text-slate-500">Create the institution, its licence, and its first administrator in one secure transaction.</p>
    </div>

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <p class="font-black">Please correct the following:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('platform.schools.store') }}" class="space-y-6">
        @csrf

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-black text-slate-950">1. School information</h2>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <label class="md:col-span-2"><span class="text-xs font-bold text-slate-700">School name</span><input name="name" value="{{ old('name') }}" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3" placeholder="e.g. Kampala Hills Secondary School"></label>
                <label><span class="text-xs font-bold text-slate-700">School type</span><select name="school_type" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3">@foreach(['kindergarten','primary','secondary','combined','tertiary'] as $type)<option value="{{ $type }}" @selected(old('school_type', 'secondary') === $type)>{{ ucfirst($type) }}</option>@endforeach</select></label>
                <label><span class="text-xs font-bold text-slate-700">School email</span><input type="email" name="email" value="{{ old('email') }}" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3" placeholder="office@school.test"></label>
                <label><span class="text-xs font-bold text-slate-700">Phone</span><input name="phone" value="{{ old('phone') }}" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3"></label>
                <label><span class="text-xs font-bold text-slate-700">Address</span><input name="address" value="{{ old('address') }}" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3"></label>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-black text-slate-950">2. Licence</h2>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <label><span class="text-xs font-bold text-slate-700">Package</span><select name="license_plan" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3">@foreach($plans as $key => $plan)<option value="{{ $key }}" @selected(old('license_plan', 'basic') === $key)>{{ $plan['name'] }}</option>@endforeach</select></label>
                <label><span class="text-xs font-bold text-slate-700">Status</span><select name="license_status" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3"><option value="active" @selected(old('license_status') === 'active')>Active customer</option><option value="trial" @selected(old('license_status', 'trial') === 'trial')>Trial / demo</option><option value="suspended" @selected(old('license_status') === 'suspended')>Suspended</option></select></label>
                <label><span class="text-xs font-bold text-slate-700">Starts</span><input type="date" name="license_started_at" value="{{ old('license_started_at', now()->toDateString()) }}" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3"></label>
                <label><span class="text-xs font-bold text-slate-700">Expires</span><input type="date" name="license_expires_at" value="{{ old('license_expires_at') }}" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3"><span class="mt-1 block text-xs text-slate-500">Required for trials; optional for active customers.</span></label>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-black text-slate-950">3. First school administrator</h2>
            <p class="mt-1 text-sm text-slate-500">Share these credentials securely. The administrator receives full school-level access.</p>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <label><span class="text-xs font-bold text-slate-700">Administrator name</span><input name="admin_name" value="{{ old('admin_name') }}" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3"></label>
                <label><span class="text-xs font-bold text-slate-700">Login email</span><input type="email" name="admin_email" value="{{ old('admin_email') }}" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3"></label>
                <label><span class="text-xs font-bold text-slate-700">Temporary password</span><input type="password" name="admin_password" required minlength="8" autocomplete="new-password" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3"></label>
                <label><span class="text-xs font-bold text-slate-700">Confirm password</span><input type="password" name="admin_password_confirmation" required minlength="8" autocomplete="new-password" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3"></label>
            </div>
        </section>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('platform.schools') }}" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700">Cancel</a>
            <button class="rounded-xl bg-amber-400 px-6 py-3 text-sm font-black text-slate-950 hover:bg-amber-300">Create school and administrator</button>
        </div>
    </form>
</div>
@endsection