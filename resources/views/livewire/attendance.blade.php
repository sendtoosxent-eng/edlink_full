<div class="space-y-6">

    <!-- HEADER BLOCK (DARK SLATE WITH YELLOW RING & TABS) -->
    <div class="bg-slate-900 rounded-2xl p-6 sm:p-8 text-white shadow-sm border border-slate-800 ring-2 ring-yellow-400">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    
                    @if($term)
                        <span class="text-xs font-medium text-slate-400">
                            • {{ $term->name }}, {{ $term->year }}
                        </span>
                    @endif
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-amber-300">
                    Students Attendance Register
                </h1>
                <p class="text-sm text-slate-400 mt-2 leading-relaxed">
                    Daily register and learner attendance performance.
                </p>
            </div>

            <!-- TAB SWITCHER -->
            <div class="inline-flex p-1.5 bg-slate-800/90 rounded-xl border border-slate-700 shrink-0">
                <button wire:click="$set('activeTab','mark')" 
                        class="px-4 py-2 rounded-lg text-xs font-bold transition cursor-pointer {{ $activeTab === 'mark' ? 'bg-yellow-400 text-slate-950 shadow-xs' : 'text-slate-400 hover:text-white' }}">
                    Daily register
                </button>
                <button wire:click="$set('activeTab','performance')" 
                        class="px-4 py-2 rounded-lg text-xs font-bold transition cursor-pointer {{ $activeTab === 'performance' ? 'bg-yellow-400 text-slate-950 shadow-xs' : 'text-slate-400 hover:text-white' }}">
                    Performance report
                </button>
            </div>
        </div>
    </div>

    <!-- ALERTS -->
    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-semibold text-rose-800">
            {{ session('error') }}
        </div>
    @endif

    <!-- FILTERS -->
    <section class="rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-xs">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 tracking-wide mb-1.5">
                    {{ $activeTab === 'mark' ? 'Attendance date' : 'From' }}
                </label>
                <input wire:model.live="{{ $activeTab === 'mark' ? 'attendanceDate' : 'reportFrom' }}" 
                       type="date" 
                       class="w-full text-xs font-medium bg-slate-50/50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent transition">
            </div>

            @if($activeTab === 'performance')
                <div>
                    <label class="block text-xs font-semibold text-slate-600 tracking-wide mb-1.5">To</label>
                    <input wire:model.live="reportTo" 
                           type="date" 
                           class="w-full text-xs font-medium bg-slate-50/50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent transition">
                </div>
            @endif

            <div>
                <label class="block text-xs font-semibold text-slate-600 tracking-wide mb-1.5">Class</label>
                <select wire:model.live="schoolClassId" 
                        class="w-full text-xs font-medium bg-slate-50/50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent transition">
                    <option value="">All classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 tracking-wide mb-1.5">Stream</label>
                <select wire:model.live="streamId" 
                        class="w-full text-xs font-medium bg-slate-50/50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent transition">
                    <option value="">All streams</option>
                    @foreach($streams as $stream)
                        <option value="{{ $stream->id }}">{{ $stream->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <label class="mt-4 inline-flex cursor-pointer items-center gap-3 text-xs font-bold text-slate-700">
            <input type="checkbox" wire:model.live="showStudentImages" class="peer sr-only">
            <span class="relative h-6 w-11 rounded-full bg-slate-200 transition peer-checked:bg-yellow-400"><span class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white shadow-sm transition peer-checked:translate-x-5"></span></span>
            Show student images
        </label>
    </section>

    <!-- TAB 1: MARK REGISTER -->
    @if($activeTab === 'mark')
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-4 shadow-xs">
                <p class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">PRESENT</p>
                <p class="mt-1 text-2xl font-extrabold text-emerald-600">{{ $selectedDay->where('status', 'present')->count() }}</p>
            </div>

            <div class="rounded-2xl border border-amber-100 bg-amber-50/40 p-4 shadow-xs">
                <p class="text-[10px] font-bold text-amber-700 uppercase tracking-wider">LATE</p>
                <p class="mt-1 text-2xl font-extrabold text-amber-600">{{ $selectedDay->where('status', 'late')->count() }}</p>
            </div>

            <div class="rounded-2xl border border-rose-100 bg-rose-50/40 p-4 shadow-xs">
                <p class="text-[10px] font-bold text-rose-700 uppercase tracking-wider">ABSENT</p>
                <p class="mt-1 text-2xl font-extrabold text-rose-600">{{ $selectedDay->where('status', 'absent')->count() }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">EXCUSED</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-700">{{ $selectedDay->where('status', 'excused')->count() }}</p>
            </div>
        </div>

        <form wire:submit="save" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-400 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-4">Learner</th>
                            <th class="p-4">Class / stream</th>
                            <th class="p-4">Attendance</th>
                            <th class="p-4">Reason / late minutes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($students as $student)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="p-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                    @if($showStudentImages)
                                        @if($student->photoUrl())<img src="{{ $student->photoUrl() }}" alt="{{ $student->name }}" class="h-12 w-12 rounded-full object-cover ring-2 ring-slate-100">@else<span class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-sm font-bold text-amber-700">{{ strtoupper(substr($student->name, 0, 1)) }}</span>@endif
                                    @endif
                                    @if(\App\Support\TeacherAcademicScope::isTeacher(auth()->user()) || auth()->user()->hasPermission('students.view') || auth()->user()->hasPermission('students.manage'))
                                        <a href="{{ route('students.profile', $student) }}" wire:navigate class="font-bold text-slate-800 hover:text-amber-700">{{ $student->name }}</a>
                                    @else<span class="font-bold text-slate-800">{{ $student->name }}</span>@endif
                                    <span class="ml-2 text-[11px] font-normal text-slate-400">{{ $student->admission_no }}</span>
                                    </div>
                                </td>
                                <td class="p-4 whitespace-nowrap text-slate-500 font-medium">
                                    {{ $student->displaySchoolClass($term)?->name }} {{ $student->displayStream($term) ? '· '.$student->displayStream($term)->name : '' }}
                                </td>
                                <td class="p-4 whitespace-nowrap">
                                    <select wire:model="statuses.{{ $student->id }}" 
                                            class="text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent transition">
                                        <option value="present">Present</option>
                                        <option value="absent">Absent</option>
                                        <option value="late">Late</option>
                                        <option value="excused">Excused</option>
                                    </select>
                                </td>
                                <td class="p-4 min-w-52">
                                    @if(($statuses[$student->id] ?? 'present') === 'late')<input type="number" min="1" max="1440" wire:model.live="notes.{{ $student->id }}" placeholder="Minutes late" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-yellow-400">@elseif(($statuses[$student->id] ?? 'present') !== 'present')<input type="text" wire:model.live="notes.{{ $student->id }}" placeholder="Reason required" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-yellow-400">@else<span class="text-xs text-slate-400">—</span>@endif
                                    @error('notes.'.$student->id)<p class="mt-1 text-[10px] font-semibold text-rose-600">{{ $message }}</p>@enderror
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-10 text-center text-slate-400 font-medium">No active learners found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($term?->isOpen())
                <div class="border-t border-slate-100 bg-slate-50/50 p-4 text-right">
                    <button type="submit" 
                            class="rounded-xl bg-yellow-400 hover:bg-yellow-300 text-slate-950 px-5 py-2.5 text-xs font-extrabold transition active:scale-95 shadow-xs cursor-pointer">
                        Save attendance
                    </button>
                </div>
            @endif
        </form>

    <!-- TAB 2: PERFORMANCE REPORT -->
    @else
        <div class="grid gap-3 sm:grid-cols-4">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">LEARNERS</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $performance->count() }}</p>
            </div>

            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-4 shadow-xs">
                <p class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">AVERAGE RATE</p>
                <p class="mt-1 text-2xl font-extrabold text-emerald-600">{{ number_format($performance->avg('rate') ?? 0, 1) }}%</p>
            </div>

            <div class="rounded-2xl border border-rose-100 bg-rose-50/40 p-4 shadow-xs">
                <p class="text-[10px] font-bold text-rose-700 uppercase tracking-wider">AT RISK (&lt;80%)</p>
                <p class="mt-1 text-2xl font-extrabold text-rose-600">{{ $performance->where('rate', '<', 80)->count() }}</p>
            </div>

            <div class="rounded-2xl border border-amber-100 bg-amber-50/40 p-4 shadow-xs">
                <p class="text-[10px] font-bold text-amber-700 uppercase tracking-wider">ABSENCES</p>
                <p class="mt-1 text-2xl font-extrabold text-amber-600">{{ $performance->sum('absent') }}</p>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5">
                <div>
                    <h2 class="font-bold text-slate-900 text-sm">Learner Attendance Performance</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Present and late are counted as attendance.</p>
                </div>
                <button wire:click="exportPerformance" 
                        class="rounded-xl border border-emerald-600 px-4 py-2 text-xs font-bold text-emerald-700 hover:bg-emerald-50 transition cursor-pointer">
                    Export Excel (.csv)
                </button>
            </header>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-400 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-4">Learner</th>
                            <th class="p-4">Recorded</th>
                            <th class="p-4">Present / late</th>
                            <th class="p-4">Absent</th>
                            <th class="p-4">Excused</th>
                            <th class="min-w-48 p-4">Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($performance as $row)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="p-4 whitespace-nowrap">
                                    @if($showStudentImages && $row['photo_url'])<img src="{{ $row['photo_url'] }}" alt="{{ $row['name'] }}" class="mb-2 h-9 w-9 rounded-full object-cover ring-2 ring-slate-100">@endif
                                    <span class="font-bold text-slate-800">{{ $row['name'] }}</span>
                                    <span class="block text-[11px] text-slate-400 mt-0.5">{{ $row['admission_no'] }} · {{ $row['class'] }}</span>
                                </td>
                                <td class="p-4 whitespace-nowrap font-bold text-slate-700">{{ $row['total'] }}</td>
                                <td class="p-4 whitespace-nowrap font-bold text-emerald-600">
                                    {{ $row['present'] }} <span class="text-[11px] font-normal text-slate-400">({{ $row['late'] }} late)</span>
                                </td>
                                <td class="p-4 whitespace-nowrap font-bold text-rose-600">{{ $row['absent'] }}</td>
                                <td class="p-4 whitespace-nowrap font-medium text-slate-600">{{ $row['excused'] }}</td>
                                <td class="p-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full {{ $row['rate'] >= 80 ? 'bg-emerald-500' : ($row['rate'] >= 60 ? 'bg-amber-400' : 'bg-rose-500') }}" 
                                                 style="width: {{ $row['rate'] }}%"></div>
                                        </div>
                                        <span class="w-12 text-right font-bold text-xs text-slate-800">{{ $row['rate'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-10 text-center text-slate-400 font-medium">No attendance records found in this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

</div>
