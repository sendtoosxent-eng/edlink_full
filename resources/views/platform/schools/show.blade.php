@extends('layouts.platform', ['title' => $school->name])

@section('content')
<div class="space-y-6">
    <a href="{{ route('platform.schools') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-amber-600">
        <span>←</span> Back to schools
    </a>

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-black">The import was not completed:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
    @endif

    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 text-white shadow-sm sm:p-8">
        <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border border-amber-300/30 bg-amber-400 text-2xl font-black text-slate-950">
                    {{ strtoupper(substr($school->name, 0, 1)) }}
                </div>
                <div>
                    <div class="mb-2 flex flex-wrap items-center gap-2">
                        @if($school->is_demo)<span class="rounded-full border border-sky-300/20 bg-sky-400/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-sky-300">Demo school</span>@endif
                        <span class="rounded-full border border-amber-300/20 bg-amber-400/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-amber-300">{{ $school->license_status }}</span>
                    </div>
                    <h1 class="text-2xl font-black tracking-tight text-amber-300 sm:text-3xl">{{ $school->name }}</h1>
                    <p class="mt-1 font-mono text-xs font-semibold text-slate-400">{{ $school->school_number }} · {{ ucfirst($school->school_type) }}</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('platform.schools.edit', $school) }}" class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/10 px-5 py-3 text-xs font-extrabold text-white transition hover:bg-white/20">Edit school details</a>
                <a href="{{ route('platform.licences') }}#school-{{ $school->id }}" class="inline-flex items-center justify-center rounded-xl bg-amber-400 px-5 py-3 text-xs font-extrabold text-slate-950 transition hover:bg-amber-300">Manage subscription</a>
            </div>
        </div>
        <div class="pointer-events-none absolute -bottom-20 -right-10 h-64 w-64 rounded-full bg-amber-400/10 blur-3xl"></div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Active learners', number_format($school->active_students_count), number_format($school->students_count).' total'],
            ['Staff accounts', number_format($school->users_count), 'Registered users'],
            ['Classes & streams', number_format($school->classes_count).' / '.number_format($school->streams_count), 'Academic structure'],
            ['Terms', number_format($school->terms_count), 'Created periods'],
        ] as [$label,$value,$hint])
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ $label }}</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ $value }}</p>
                <p class="mt-1 text-xs font-medium text-slate-400">{{ $hint }}</p>
            </div>
        @endforeach
    </section>

    <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-600">Bulk onboarding</p>
                <h2 class="mt-1 text-base font-black text-slate-900">Import students and teachers</h2>
                <p class="mt-1 text-xs leading-5 text-slate-500">Use the templates exactly as supplied. Imports are school-scoped and all-or-nothing: any invalid row cancels the file.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('platform.imports.template', 'students') }}" class="rounded-xl border border-slate-200 px-3 py-2 text-[10px] font-black text-slate-700 hover:border-amber-300">Student template</a>
                <a href="{{ route('platform.imports.template', 'teachers') }}" class="rounded-xl border border-slate-200 px-3 py-2 text-[10px] font-black text-slate-700 hover:border-amber-300">Teacher template</a>
            </div>
        </div>
        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            <form method="POST" enctype="multipart/form-data" action="{{ route('platform.schools.imports.students', $school) }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                @csrf
                <h3 class="text-sm font-black text-slate-900">Students CSV</h3>
                <p class="mt-1 text-[11px] leading-5 text-slate-500">Requires an open current term plus matching class, category, and optional stream names.</p>
                <input type="file" name="file" accept=".csv,text/csv" required class="mt-4 block w-full rounded-xl border border-slate-200 bg-white p-2 text-xs">
                <button class="mt-3 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-black text-white hover:bg-slate-800">Import students</button>
            </form>
            <form method="POST" enctype="multipart/form-data" action="{{ route('platform.schools.imports.teachers', $school) }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                @csrf
                <h3 class="text-sm font-black text-slate-900">Teachers CSV</h3>
                <p class="mt-1 text-[11px] leading-5 text-slate-500">Designation names must already exist. Temporary passwords must contain at least eight characters.</p>
                <input type="file" name="file" accept=".csv,text/csv" required class="mt-4 block w-full rounded-xl border border-slate-200 bg-white p-2 text-xs">
                <button class="mt-3 rounded-xl bg-amber-400 px-4 py-2.5 text-xs font-black text-slate-950 hover:bg-amber-300">Import teachers</button>
            </form>
        </div>
    </section>
    <div class="grid gap-6 xl:grid-cols-3">
        <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm xl:col-span-2">
            <h2 class="text-base font-black text-slate-900">School information</h2>
            <p class="mt-1 text-xs text-slate-400">Registration, contact, and subscription details.</p>
            <dl class="mt-6 grid gap-x-8 gap-y-5 sm:grid-cols-2">
                @foreach([
                    ['Email', $school->email ?: 'Not provided'],
                    ['Phone', $school->phone ?: 'Not provided'],
                    ['Address', $school->address ?: 'Not provided'],
                    ['Website', $school->website ?: 'Not provided'],
                    ['Principal', $school->principal_name ?: 'Not provided'],
                    ['Motto', $school->motto ?: 'Not provided'],
                    ['Plan', ucfirst($school->license_plan ?: 'Not assigned')],
                    ['Learner limit', $school->license_student_limit ? number_format($school->license_student_limit) : 'Unlimited'],
                    ['Licence started', $school->license_started_at?->format('d M Y') ?: 'Not set'],
                    ['Licence expires', $school->license_expires_at?->format('d M Y') ?: 'Not set'],
                    ['Registered', $school->created_at->format('d M Y, H:i')],
                    ['Last updated', $school->updated_at->diffForHumans()],
                ] as [$label,$value])
                    <div class="border-b border-slate-100 pb-3">
                        <dt class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ $label }}</dt>
                        <dd class="mt-1 break-words text-sm font-bold text-slate-800">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <div class="space-y-6">
            <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-black text-slate-900">Recent staff accounts</h2>
                <div class="mt-4 divide-y divide-slate-100">
                    @forelse($school->users as $user)
                        <div class="py-3">
                            <p class="truncate text-xs font-bold text-slate-800">{{ $user->name }}</p>
                            <p class="mt-0.5 truncate text-[10px] text-slate-400">{{ $user->email }} · {{ ucfirst($user->role) }}</p>
                        </div>
                    @empty
                        <p class="py-5 text-xs text-slate-400">No staff accounts.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border {{ $canDelete ? 'border-rose-200 bg-rose-50' : 'border-slate-200 bg-slate-50' }} p-6">
                <h2 class="text-sm font-black {{ $canDelete ? 'text-rose-900' : 'text-slate-700' }}">Remove school</h2>
                @if($canDelete)
                    <p class="mt-2 text-xs leading-5 text-rose-700">This permanently deletes the school, its accounts, learners, and tenant records. This cannot be undone.</p>
                    <form method="POST" action="{{ route('platform.schools.destroy', $school) }}" class="mt-4" onsubmit="return confirm('Permanently remove {{ addslashes($school->name) }} and all of its tenant data?')">
                        @csrf @method('DELETE')
                        <label class="block text-[10px] font-black uppercase tracking-wider text-rose-700">Type {{ $school->school_number }} to confirm</label>
                        <input name="school_number" required autocomplete="off" class="mt-2 w-full rounded-xl border border-rose-200 bg-white px-3 py-2.5 font-mono text-xs font-bold text-slate-900 outline-none focus:border-rose-400 focus:ring-4 focus:ring-rose-200/40">
                        <button class="mt-3 w-full rounded-xl bg-rose-600 px-4 py-3 text-xs font-black text-white hover:bg-rose-700">Permanently remove school</button>
                    </form>
                @else
                    <p class="mt-2 text-xs leading-5 text-slate-500">Active customer schools are protected. Suspend or expire the licence before removal.</p>
                @endif
            </section>
        </div>
    </div>
</div>
@endsection
