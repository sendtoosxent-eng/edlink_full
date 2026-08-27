<x-app-layout>
@php
    $isStudent = $type === 'student';
    $isStaff = $type === 'staff';
    $photoUrl = $isStudent ? $person->photoUrl() : $person->avatarUrl();
    $title = $isStudent ? 'Student Profile' : ($isStaff ? 'Staff Profile' : 'Parent Profile');
@endphp
<div class="space-y-6">
    <header class="overflow-hidden rounded-3xl bg-slate-900 p-6 text-white shadow-xl sm:p-8">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-5">
                <div class="flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-3xl border-4 border-amber-300/40 bg-slate-800 text-4xl font-black text-amber-300 shadow-xl">
                    @if($photoUrl)<img src="{{ $photoUrl }}" alt="{{ $person->name }}" class="h-full w-full object-cover">@else{{ str($person->name)->substr(0, 1)->upper() }}@endif
                </div>
                <div><p class="text-xs font-black uppercase tracking-[.2em] text-amber-300">{{ $title }}</p><h1 class="mt-2 text-2xl font-black sm:text-3xl">{{ $person->name }}</h1><p class="mt-1 text-sm text-slate-300">{{ $isStudent ? ($person->admission_no ?: 'Admission number pending') : ($person->email ?: 'No email') }}</p></div>
            </div>
            <a href="{{ $backRoute }}" wire:navigate class="rounded-xl border border-white/15 bg-white/10 px-4 py-2.5 text-sm font-bold hover:bg-white/15">Back to directory</a>
        </div>
    </header>

    @if(session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-black text-slate-900">Profile photograph</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500">Use a clear, front-facing portrait. Student and staff photographs are reused automatically when printing ID cards.</p>
            <div class="mx-auto mt-6 flex aspect-[3/4] max-w-60 items-center justify-center overflow-hidden rounded-3xl border-4 border-amber-100 bg-slate-100 text-6xl font-black text-slate-300">
                @if($photoUrl)<img src="{{ $photoUrl }}" alt="{{ $person->name }}" class="h-full w-full object-cover">@else{{ str($person->name)->substr(0, 1)->upper() }}@endif
            </div>
            <div class="mt-5 rounded-xl bg-amber-50 p-3 text-xs font-semibold leading-5 text-amber-900">Accepted: JPG, PNG or WebP · maximum 4 MB. The saved photo remains attached to this profile until replaced.</div>
        </aside>

        <form method="POST" action="{{ $updateRoute }}" enctype="multipart/form-data" class="rounded-3xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
            @csrf @method('PATCH')
            <div class="border-b border-slate-100 px-6 py-5"><h2 class="text-lg font-black text-slate-900">Identity and contact information</h2><p class="mt-1 text-xs text-slate-500">Review the record and save any corrections.</p></div>
            <div class="grid gap-5 p-6 sm:grid-cols-2">
                <label class="text-sm font-bold text-slate-700">Full name<input name="name" value="{{ old('name', $person->name) }}" required @disabled(!$canEdit) class="mt-2 w-full rounded-xl border-slate-200 focus:border-amber-400 focus:ring-amber-400"></label>
                @if($isStudent)
                    <label class="text-sm font-bold text-slate-700">Admission number<input name="admission_no" value="{{ old('admission_no', $person->admission_no) }}" @disabled(!$canEdit) class="mt-2 w-full rounded-xl border-slate-200"></label>
                    <label class="text-sm font-bold text-slate-700">Date of birth<input type="date" name="date_of_birth" value="{{ old('date_of_birth', $person->date_of_birth?->toDateString()) }}" @disabled(!$canEdit) class="mt-2 w-full rounded-xl border-slate-200"></label>
                    <label class="text-sm font-bold text-slate-700">Gender<select name="gender" @disabled(!$canEdit) class="mt-2 w-full rounded-xl border-slate-200"><option value="">Not specified</option><option value="male" @selected(old('gender',$person->gender)==='male')>Male</option><option value="female" @selected(old('gender',$person->gender)==='female')>Female</option></select></label>
                    <label class="text-sm font-bold text-slate-700">Class<input value="{{ $person->schoolClass?->name ?: 'Not assigned' }}" disabled class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50"></label>
                    <label class="text-sm font-bold text-slate-700">Stream<input value="{{ $person->stream?->name ?: 'Not assigned' }}" disabled class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50"></label>
                    <label class="text-sm font-bold text-slate-700">Nationality<input name="nationality" value="{{ old('nationality',$person->nationality) }}" @disabled(!$canEdit) class="mt-2 w-full rounded-xl border-slate-200"></label>
                    <label class="text-sm font-bold text-slate-700">Religion<input name="religion" value="{{ old('religion',$person->religion) }}" @disabled(!$canEdit) class="mt-2 w-full rounded-xl border-slate-200"></label>
                    <label class="text-sm font-bold text-slate-700">Blood group<input name="blood_group" value="{{ old('blood_group',$person->blood_group) }}" @disabled(!$canEdit) class="mt-2 w-full rounded-xl border-slate-200"></label>
                    <label class="text-sm font-bold text-slate-700 sm:col-span-2">Home address<textarea name="home_address" @disabled(!$canEdit) class="mt-2 w-full rounded-xl border-slate-200">{{ old('home_address',$person->home_address) }}</textarea></label>
                    <label class="text-sm font-bold text-slate-700 sm:col-span-2">Medical notes<textarea name="medical_notes" @disabled(!$canEdit) class="mt-2 w-full rounded-xl border-slate-200">{{ old('medical_notes',$person->medical_notes) }}</textarea></label>
                @else
                    <label class="text-sm font-bold text-slate-700">Email address<input type="email" name="email" value="{{ old('email',$person->email) }}" required @disabled(!$canEdit) class="mt-2 w-full rounded-xl border-slate-200"></label>
                    <label class="text-sm font-bold text-slate-700">Phone number<input name="phone" value="{{ old('phone',$person->phone) }}" @disabled(!$canEdit) class="mt-2 w-full rounded-xl border-slate-200"></label>
                    @if($isStaff)<label class="text-sm font-bold text-slate-700">Job title<input name="job_title" value="{{ old('job_title',$person->job_title) }}" required @disabled(!$canEdit) class="mt-2 w-full rounded-xl border-slate-200"></label><label class="text-sm font-bold text-slate-700">Designation<input value="{{ $person->designation?->name ?: 'Not assigned' }}" disabled class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50"></label>@endif
                    @if(!$isStaff)<div class="sm:col-span-2"><p class="text-sm font-bold text-slate-700">Linked learners</p><div class="mt-2 flex flex-wrap gap-2">@forelse($person->portalStudents as $student)<span class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700">{{ $student->name }} · {{ $student->schoolClass?->name }}</span>@empty<span class="text-sm text-slate-400">No learners linked.</span>@endforelse</div></div>@endif
                @endif
                @if($canEdit)<label class="text-sm font-bold text-slate-700 sm:col-span-2">Upload or replace photograph<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full rounded-xl border border-slate-200 p-3 text-sm"></label>@endif
            </div>
            @if($canEdit)<div class="flex justify-end border-t bg-slate-50 px-6 py-4"><button class="rounded-xl bg-amber-400 px-6 py-3 text-sm font-black text-slate-950 hover:bg-amber-300">Save profile</button></div>@endif
        </form>
    </div>
</div>
</x-app-layout>
