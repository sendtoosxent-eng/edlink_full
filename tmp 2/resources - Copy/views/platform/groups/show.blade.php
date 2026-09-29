@extends('layouts.platform', ['title' => $group->name])
@section('content')
<div class="space-y-6">
    <a href="{{ route('platform.groups.index') }}" class="text-xs font-bold text-slate-500">← Back to school groups</a>
    @if(session('status'))<div class="rounded-xl bg-emerald-50 p-4 text-sm font-bold text-emerald-800">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="rounded-xl bg-rose-50 p-4 text-sm text-rose-700">{{ $errors->first() }}</div>@endif

    <section class="rounded-3xl bg-slate-900 p-7 text-white">
        <p class="text-xs font-black uppercase tracking-widest text-amber-300">{{ $group->code }}</p>
        <h1 class="mt-2 text-3xl font-black">{{ $group->name }}</h1>
        <p class="mt-2 text-sm text-slate-300">{{ $group->schools->count() }} independently licensed branches</p>
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border bg-white p-5">
            <h2 class="font-black">Branches</h2>
            <div class="mt-4 divide-y">
                @foreach($group->schools as $school)
                    <a href="{{ route('platform.schools.show', $school) }}" class="flex justify-between py-4">
                        <span><b class="block text-sm">{{ $school->branch_name ?: $school->name }}</b><small class="text-slate-400">{{ $school->school_number }}</small></span>
                        <span class="text-xs text-slate-500">{{ number_format($school->students_count) }} learners</span>
                    </a>
                @endforeach
            </div>
            @if($availableSchools->isNotEmpty())
                <form method="POST" action="{{ route('platform.groups.branches.store', $group) }}" class="mt-4 grid gap-3 border-t pt-4 sm:grid-cols-2">@csrf
                    <select name="school_id" class="rounded-xl border-slate-200 text-xs">@foreach($availableSchools as $school)<option value="{{ $school->id }}">{{ $school->name }}</option>@endforeach</select>
                    <input name="branch_name" class="rounded-xl border-slate-200 text-xs" placeholder="Branch label (optional)">
                    <button class="rounded-xl bg-slate-900 px-4 py-3 text-xs font-bold text-white sm:col-span-2">Add branch</button>
                </form>
            @endif
        </section>

        <section class="rounded-2xl border bg-white p-5">
            <h2 class="font-black">Assign staff to a branch</h2>
            <p class="mt-1 text-xs text-slate-500">A staff account must originate in this group. Save once per branch: the same person can be Teacher in one branch and Bursar in another, or Bursar in both.</p>
            <form method="POST" action="{{ route('platform.groups.access.store', $group) }}" class="mt-4 grid gap-3 border-t pt-4 sm:grid-cols-2">@csrf
                <label class="sm:col-span-2"><span class="mb-1 block text-xs font-bold text-slate-600">Staff email</span><input name="email" type="email" required class="w-full rounded-xl border-slate-200 text-sm" placeholder="staff@school.com" value="{{ old('email') }}"></label>
                <label><span class="mb-1 block text-xs font-bold text-slate-600">Branch</span><select name="school_id" required class="w-full rounded-xl border-slate-200 text-xs">@foreach($group->schools as $school)<option value="{{ $school->id }}">{{ $school->branch_name ?: $school->name }}</option>@endforeach</select></label>
                <label><span class="mb-1 block text-xs font-bold text-slate-600">Role in this branch</span><select name="role" required class="w-full rounded-xl border-slate-200 text-xs"><option value="teacher">Teacher</option><option value="bursar">Bursar</option><option value="academic_admin">Academic administrator</option><option value="registrar">Registrar</option><option value="admin">Administrator</option></select></label>
                <label class="sm:col-span-2"><span class="mb-1 block text-xs font-bold text-slate-600">Designation and permissions (optional)</span><select name="designation_id" class="w-full rounded-xl border-slate-200 text-xs"><option value="">Use role defaults / no designation</option>@foreach($designations as $designation)<option value="{{ $designation->id }}">{{ $designation->school->branch_name ?: $designation->school->name }} — {{ $designation->name }}</option>@endforeach</select><small class="mt-1 block text-slate-400">The designation must belong to the selected branch.</small></label>
                <label class="flex items-center gap-2 rounded-xl bg-slate-50 p-3 text-xs font-bold text-slate-700 sm:col-span-2"><input type="checkbox" name="can_view_group" value="1" class="rounded border-slate-300 text-amber-500"> Allow consolidated All branches reports and graphs</label>
                <button class="rounded-xl bg-amber-400 px-4 py-3 text-xs font-black text-slate-950 sm:col-span-2">Save branch assignment</button>
            </form>
        </section>
    </div>

    <section class="overflow-hidden rounded-2xl border bg-white">
        <div class="border-b p-5"><h2 class="font-black">Current staff branch assignments</h2><p class="mt-1 text-xs text-slate-500">Roles and permissions apply only while the staff member is working inside the listed branch.</p></div>
        <div class="overflow-x-auto"><table class="w-full min-w-[760px] text-left text-xs"><thead class="bg-slate-50 uppercase text-slate-500"><tr><th class="px-5 py-3">Staff</th><th class="px-5 py-3">Branch</th><th class="px-5 py-3">Role</th><th class="px-5 py-3">Designation</th><th class="px-5 py-3">Group reports</th></tr></thead><tbody class="divide-y">
            @forelse($assignments as $assignment)<tr><td class="px-5 py-4"><b class="block text-sm text-slate-900">{{ $assignment->user_name }}</b><span class="text-slate-400">{{ $assignment->email }}</span></td><td class="px-5 py-4 font-bold">{{ $assignment->branch_name ?: $assignment->school_name }}</td><td class="px-5 py-4">{{ str($assignment->role)->replace('_', ' ')->title() }}</td><td class="px-5 py-4">{{ $assignment->designation_name ?: 'Role defaults' }}</td><td class="px-5 py-4"><span class="rounded-full px-2 py-1 font-bold {{ $assignment->can_view_group ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $assignment->can_view_group ? 'Allowed' : 'No' }}</span></td></tr>@empty<tr><td colspan="5" class="p-8 text-center text-slate-400">No staff assignments found.</td></tr>@endforelse
        </tbody></table></div>
    </section>
</div>
@endsection
