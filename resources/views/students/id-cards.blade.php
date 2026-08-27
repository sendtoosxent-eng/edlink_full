<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-amber-300 leading-tight">School ID Cards</h2></x-slot>
    <div class="space-y-6">
        <header class="relative overflow-hidden rounded-3xl bg-slate-900 p-6 text-white shadow-xl sm:p-8">
            <div class="relative z-10 max-w-2xl">
                <h1 class="mt-2 text-2xl font-extrabold sm:text-3xl text-amber-300">Create school ID cards</h1>
                <p class="mt-2 text-sm leading-6 text-slate-300">Choose students or staff, narrow the list, select the people you need, then generate branded portrait cards.</p></div>
            <div class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-amber-400/15 blur-3xl"></div>
        </header>
        <div class="grid grid-cols-2 gap-3 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm sm:w-[430px]">
            <a href="{{ route('students.id-cards', ['type' => 'student']) }}" class="rounded-xl px-4 py-3 text-center text-sm font-bold transition {{ $type === 'student' ? 'bg-slate-900 text-white shadow' : 'text-slate-500 hover:bg-slate-50' }}">Students</a>
            <a href="{{ route('students.id-cards', ['type' => 'staff']) }}" class="rounded-xl px-4 py-3 text-center text-sm font-bold transition {{ $type === 'staff' ? 'bg-slate-900 text-white shadow' : 'text-slate-500 hover:bg-slate-50' }}">Staff</a>
        </div>
        <form method="GET" action="{{ route('students.id-cards') }}" class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-[1fr_260px_auto] md:items-end">
            <input type="hidden" name="type" value="{{ $type }}">
            <label><span class="text-xs font-bold uppercase tracking-wider text-slate-500">Name or {{ $type === 'student' ? 'admission' : 'staff' }} number</span><input name="search" value="{{ request('search') }}" placeholder="Start typing a name or number..." class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-amber-400 focus:bg-white focus:ring-amber-400"></label>
            @if($type === 'student')
                <label><span class="text-xs font-bold uppercase tracking-wider text-slate-500">Class</span><select name="class_id" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm"><option value="">All classes</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected((string) request('class_id') === (string) $class->id)>{{ $class->name }}</option>@endforeach</select></label>
            @else
                <label><span class="text-xs font-bold uppercase tracking-wider text-slate-500">Role</span><select name="role" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm"><option value="">All roles</option>@foreach($roles as $role)<option value="{{ $role }}" @selected(request('role') === $role)>{{ str($role)->replace('_', ' ')->title() }}</option>@endforeach</select></label>
            @endif
            <button class="rounded-xl bg-amber-400 px-6 py-3 text-sm font-extrabold text-slate-900 transition hover:-translate-y-0.5 hover:bg-amber-300">Apply filters</button>
        </form>
        <form method="POST" action="{{ route('students.id-cards.generate') }}" target="_blank" x-data="{ selected: [], all: false }" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @csrf<input type="hidden" name="type" value="{{ $type }}">
            <div class="flex flex-col gap-3 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="font-extrabold text-slate-900">{{ $type === 'student' ? 'Student' : 'Staff' }} directory</h2><p class="mt-1 text-xs text-slate-500">{{ $people->count() }} matching records · select up to 200</p></div><button type="submit" :disabled="selected.length === 0" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40">Proceed with <span x-text="selected.length"></span> selected</button></div>
            <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="w-14 px-5 py-4"><input type="checkbox" x-model="all" @change="selected = all ? {{ $people->pluck('id')->values()->toJson() }} : []" class="rounded border-slate-300 text-amber-500"></th><th class="px-3 py-4">Person</th><th class="px-3 py-4">Number</th><th class="px-3 py-4">{{ $type === 'student' ? 'Class' : 'Role' }}</th><th class="px-5 py-4">Status</th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse($people as $person)<tr class="transition hover:bg-amber-50/40"><td class="px-5 py-4"><input type="checkbox" name="ids[]" value="{{ $person->id }}" x-model.number="selected" class="rounded border-slate-300 text-amber-500"></td><td class="px-3 py-4"><div class="flex items-center gap-3"><div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-100 font-extrabold text-slate-500">@if($type === 'student' ? $person->photoUrl() : $person->avatarUrl())<img src="{{ $type === 'student' ? $person->photoUrl() : $person->avatarUrl() }}" class="h-full w-full object-cover">@else{{ str($person->name)->substr(0, 1)->upper() }}@endif</div><div><p class="font-bold text-slate-900">{{ $person->name }}</p><p class="text-xs text-slate-400">{{ $type === 'student' ? ucfirst($person->gender ?: 'Student') : ($person->designation?->name ?: 'School staff') }}</p></div></div></td><td class="px-3 py-4 font-mono text-xs font-bold text-slate-600">{{ $type === 'student' ? $person->admission_no : ($person->staff_number ?: '—') }}</td><td class="px-3 py-4 text-slate-600">{{ $type === 'student' ? trim(($person->schoolClass?->name ?? '').' '.($person->stream?->name ?? '')) : str($person->role)->replace('_', ' ')->title() }}</td><td class="px-5 py-4"><span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Active</span></td></tr>
                @empty<tr><td colspan="5" class="px-6 py-16 text-center text-slate-400">No matching {{ $type }} records. Change the filters and try again.</td></tr>@endforelse
            </tbody></table></div>
        </form>
    </div>
</x-app-layout>
