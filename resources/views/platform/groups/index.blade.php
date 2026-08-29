@extends('layouts.platform', ['title' => 'School Groups'])
@section('content')
<div class="space-y-6">
    @if(session('status'))<div class="rounded-xl bg-emerald-50 p-4 text-sm font-bold text-emerald-800">{{ session('status') }}</div>@endif
     <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
        <section class="overflow-hidden rounded-2xl border bg-white"><div class="border-b p-5"><h2 class="font-black">Registered groups</h2></div><div class="divide-y">
            @forelse($groups as $group)<a href="{{ route('platform.groups.show', $group) }}" class="flex items-center justify-between p-5 hover:bg-slate-50"><div><b>{{ $group->name }}</b><p class="text-xs text-slate-400">{{ $group->code }} · {{ $group->schools->pluck('name')->join(', ') }}</p></div><span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800">{{ $group->schools_count }} branches</span></a>@empty<p class="p-8 text-center text-sm text-slate-400">No school groups yet.</p>@endforelse
        </div></section>
        <section class="rounded-2xl border bg-white p-5"><h2 class="font-black">Create a school group</h2><p class="mt-1 text-xs text-slate-500">Select at least two ungrouped schools.</p>
            <form method="POST" action="{{ route('platform.groups.store') }}" class="mt-5 space-y-4">@csrf
                <label class="block text-xs font-bold">Group name<input name="name" value="{{ old('name') }}" required class="mt-2 w-full rounded-xl border-slate-200"></label>
                <label class="block text-xs font-bold">Group code<input name="code" value="{{ old('code') }}" required class="mt-2 w-full rounded-xl border-slate-200" placeholder="BFS"></label>
                <fieldset><legend class="text-xs font-bold">Branches</legend><div class="mt-2 max-h-52 space-y-2 overflow-y-auto rounded-xl border p-3">@forelse($availableSchools as $school)<label class="flex gap-2 text-xs"><input type="checkbox" name="school_ids[]" value="{{ $school->id }}"> <span>{{ $school->name }} <i class="text-slate-400">{{ $school->school_number }}</i></span></label>@empty<p class="text-xs text-slate-400">No ungrouped schools available.</p>@endforelse</div></fieldset>
                @if($errors->any())<div class="rounded-xl bg-rose-50 p-3 text-xs text-rose-700">{{ $errors->first() }}</div>@endif
                <button class="w-full rounded-xl bg-amber-400 px-4 py-3 text-xs font-black text-slate-900">Create group</button>
            </form>
        </section>
    </div>
</div>
@endsection
