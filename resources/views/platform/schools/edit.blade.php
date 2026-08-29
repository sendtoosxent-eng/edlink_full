@extends('layouts.platform', ['title' => 'Edit '.$school->name])

@section('content')
<div class="space-y-6">
    <a href="{{ route('platform.schools.show', $school) }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-amber-600">← Back to school details</a>

    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 text-white shadow-sm sm:p-8">
        <div class="relative z-10">
            <span class="rounded-full border border-amber-400/20 bg-amber-400/10 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-amber-300">School profile</span>
            <h1 class="mt-4 text-2xl font-black text-amber-300 sm:text-3xl">Edit {{ $school->name }}</h1>
            <p class="mt-2 text-sm text-slate-400">Update the school’s identity, email, contact information, and demo status.</p>
        </div>
        <div class="pointer-events-none absolute -bottom-20 -right-10 h-64 w-64 rounded-full bg-amber-400/10 blur-3xl"></div>
    </section>

    <form method="POST" action="{{ route('platform.schools.update', $school) }}" class="space-y-6">
        @csrf @method('PUT')
        <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-5">
                <div><h2 class="font-black text-slate-900">School information</h2><p class="mt-1 text-xs text-slate-400">Fields marked with * are required.</p></div>
                <span class="rounded-lg bg-slate-100 px-3 py-1.5 font-mono text-[10px] font-bold text-slate-500">{{ $school->school_number }}</span>
            </div>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <label class="block sm:col-span-2"><span class="text-xs font-bold text-slate-700">School name *</span><input name="name" value="{{ old('name', $school->name) }}" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold focus:border-amber-400 focus:bg-white focus:ring-amber-400"></label>
                <label class="block"><span class="text-xs font-bold text-slate-700">School email</span><input type="email" name="email" value="{{ old('email', $school->email) }}" placeholder="school@example.com" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold focus:border-amber-400 focus:bg-white focus:ring-amber-400">@error('email')<span class="mt-1 block text-xs font-bold text-rose-600">{{ $message }}</span>@enderror</label>
                <label class="block"><span class="text-xs font-bold text-slate-700">Phone</span><input name="phone" value="{{ old('phone', $school->phone) }}" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold focus:border-amber-400 focus:bg-white focus:ring-amber-400"></label>
                <label class="block"><span class="text-xs font-bold text-slate-700">School type *</span><select name="school_type" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold focus:border-amber-400 focus:ring-amber-400">@foreach(['kindergarten'=>'Kindergarten','primary'=>'Primary','secondary'=>'Secondary','combined'=>'Combined','tertiary'=>'Tertiary'] as $value=>$label)<option value="{{ $value }}" @selected(old('school_type', $school->school_type)===$value)>{{ $label }}</option>@endforeach</select></label>
                <label class="block"><span class="text-xs font-bold text-slate-700">Account status *</span><select name="status" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold focus:border-amber-400 focus:ring-amber-400">@foreach(['active'=>'Active','inactive'=>'Inactive'] as $value=>$label)<option value="{{ $value }}" @selected(old('status', $school->status)===$value)>{{ $label }}</option>@endforeach</select></label>
                <label class="block"><span class="text-xs font-bold text-slate-700">Principal name</span><input name="principal_name" value="{{ old('principal_name', $school->principal_name) }}" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold focus:border-amber-400 focus:bg-white focus:ring-amber-400"></label>
                <label class="block"><span class="text-xs font-bold text-slate-700">Website</span><input type="url" name="website" value="{{ old('website', $school->website) }}" placeholder="https://..." class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold focus:border-amber-400 focus:bg-white focus:ring-amber-400"></label>
                <label class="block sm:col-span-2"><span class="text-xs font-bold text-slate-700">Address</span><textarea name="address" rows="3" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold focus:border-amber-400 focus:bg-white focus:ring-amber-400">{{ old('address', $school->address) }}</textarea></label>
                <label class="block sm:col-span-2"><span class="text-xs font-bold text-slate-700">Motto</span><input name="motto" value="{{ old('motto', $school->motto) }}" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold focus:border-amber-400 focus:bg-white focus:ring-amber-400"></label>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="font-black text-slate-900">Demo access</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4"><input type="hidden" name="is_demo" value="0"><input type="checkbox" name="is_demo" value="1" @checked(old('is_demo', $school->is_demo)) class="rounded border-slate-300 text-amber-500 focus:ring-amber-400"><span><b class="block text-xs text-slate-800">Demo school</b><span class="text-[10px] text-slate-400">Mark this tenant as a demonstration account.</span></span></label>
                <label class="block"><span class="text-xs font-bold text-slate-700">Demo expiry</span><input type="datetime-local" name="demo_expires_at" value="{{ old('demo_expires_at', $school->demo_expires_at?->format('Y-m-d\TH:i')) }}" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold focus:border-amber-400 focus:ring-amber-400"></label>
            </div>
        </section>

        @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800">Please correct the highlighted information and try again.</div>@endif
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('platform.schools.show', $school) }}" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-center text-xs font-black text-slate-600 hover:bg-slate-50">Cancel</a>
            <button class="rounded-xl bg-amber-400 px-7 py-3 text-xs font-black text-slate-950 shadow-sm hover:bg-amber-300">Save school details</button>
        </div>
    </form>
</div>
@endsection
