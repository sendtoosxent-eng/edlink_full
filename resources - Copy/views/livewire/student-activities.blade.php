<div class="space-y-6">

    <!-- HEADER BLOCK -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-sm">
        <div class="relative z-10 flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-amber-300">
                    Student Houses & Clubs
                </h1>
                <p class="text-sm font-medium text-slate-400 mt-1 max-w-3xl">
                    Balance learners across houses and manage club memberships with assigned teacher patrons.
                </p>
            </div>

            <!-- TAB TOGGLE -->
            <div class="inline-flex shrink-0 self-start sm:self-center rounded-xl bg-slate-950/60 p-1.5 border border-slate-700/50 backdrop-blur-sm">
                <button type="button" 
                        wire:click="$set('tab','houses')" 
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-bold transition-all duration-200 {{ $tab==='houses' ? 'bg-amber-400 text-slate-950 shadow-md' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                    Houses
                </button>
                <button type="button" 
                        wire:click="$set('tab','clubs')" 
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-bold transition-all duration-200 {{ $tab==='clubs' ? 'bg-amber-400 text-slate-950 shadow-md' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    Clubs
                </button>
            </div>
        </div>

        <!-- Glowing Background Ambient Effect -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- ALERT NOTIFICATIONS -->
    @if(session('status'))
        <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 shadow-2xs">
            <svg class="h-5 w-5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800 shadow-2xs">
            <svg class="h-5 w-5 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- TAB 1: HOUSES -->
    @if($tab==='houses')
        <div class="grid gap-6 {{ $isManager ? 'lg:grid-cols-[360px_1fr]' : '' }}">
            
            <!-- MANAGER SIDEBAR: CREATE HOUSE -->
            @if($isManager)
                <form wire:submit="createHouse" class="h-fit rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs space-y-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Create a House</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Unassigned students are allocated automatically when saved.</p>
                    </div>

                    <div class="space-y-3.5">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">House Name</label>
                            <input wire:model="houseName" type="text" placeholder="e.g. Mandela House" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-semibold text-slate-900 transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20">
                            @error('houseName') <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">House Color</label>
                            <div class="flex items-center gap-3">
                                <input wire:model="houseColor" type="color" class="h-10 w-full cursor-pointer rounded-xl border border-slate-200 p-1">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Teacher Patron</label>
                            <select wire:model="housePatronId" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-semibold text-slate-900 transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20">
                                <option value="">No patron assigned yet</option>
                                @foreach($staff as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }}{{ $member->job_title ? ' — '.$member->job_title : '' }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Description</label>
                            <textarea wire:model="houseDescription" rows="3" placeholder="Brief details about house history or values..." class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-semibold text-slate-900 transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20"></textarea>
                        </div>

                        <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-slate-800 active:scale-95 shadow-2xs">
                            Create House & Allocate
                        </button>
                    </div>
                </form>
            @endif

            <!-- HOUSES LISTING SECTION -->
            <section class="space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">{{ $isManager ? 'Current Houses' : 'My Assigned House' }}</h2>
                        <p class="text-xs text-slate-500">
                            {{ $isManager ? 'Student totals remain within one learner of each other after rebalancing.' : 'House membership is allocated automatically by the school.' }}
                        </p>
                    </div>
                    @if($isManager)
                        <button type="button" 
                                wire:click="rebalanceHouses" 
                                wire:confirm="Redistribute every active student evenly across all houses?" 
                                class="inline-flex items-center gap-1.5 shrink-0 rounded-xl bg-amber-400 px-4 py-2 text-xs font-bold text-slate-950 transition hover:bg-amber-300 active:scale-95 shadow-2xs">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            Rebalance All Students
                        </button>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse($houses as $house)
                        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs transition hover:border-slate-300">
                            <div class="h-2.5 w-full" style="background:{{ $house->color }}"></div>
                            <div class="p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="font-bold text-slate-900 text-sm">{{ $house->name }}</h3>
                                        <p class="mt-1 text-xs font-medium text-slate-500">Patron: <span class="text-slate-800">{{ $house->patron_name ?: 'Not assigned' }}</span></p>
                                    </div>
                                    <span class="inline-flex shrink-0 items-center rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700">
                                        {{ $house->members_count }} Students
                                    </span>
                                </div>
                                @if($house->description)
                                    <p class="mt-3 text-xs text-slate-600 line-clamp-2">{{ $house->description }}</p>
                                @endif
                                <button type="button" wire:click="selectHouse({{ $house->id }})" class="mt-4 w-full rounded-xl px-3 py-2 text-xs font-bold {{ (string)$selectedHouseId===(string)$house->id ? 'bg-amber-400 text-slate-950' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">View member list</button>
                            </div>
                        </article>
                    @empty
                        <div class="col-span-full rounded-2xl border border-dashed border-slate-300 p-12 text-center text-xs font-semibold text-slate-400">
                            No student houses have been created yet.
                        </div>
                    @endforelse
                </div>

                @if($selectedHouseId)
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50 p-5">
                            <div><h3 class="font-bold text-slate-900">House members</h3><p class="text-xs text-slate-500">{{ $houseMembers->count() }} assigned students</p></div>
                            <a href="{{ route('students.activities.export',['type'=>'house','activity'=>$selectedHouseId]) }}" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700">Export for Excel</a>
                        </div>
                        <div class="overflow-x-auto"><table class="w-full min-w-[560px] text-left text-xs"><thead class="bg-slate-900 text-[10px] uppercase tracking-wider text-white"><tr><th class="px-5 py-3">Admission no.</th><th class="px-5 py-3">Student</th><th class="px-5 py-3">Class</th><th class="px-5 py-3">Gender</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($houseMembers as $member)<tr><td class="px-5 py-3 font-mono text-slate-500">{{ $member->admission_no }}</td><td class="px-5 py-3 font-bold text-slate-900">{{ $member->name }}</td><td class="px-5 py-3">{{ $member->class_name ?: '—' }}</td><td class="px-5 py-3 capitalize">{{ $member->gender ?: '—' }}</td></tr>@empty<tr><td colspan="4" class="p-8 text-center text-slate-400">No members assigned.</td></tr>@endforelse</tbody></table></div>
                    </div>
                @endif
            </section>
        </div>

    <!-- TAB 2: CLUBS -->
    @else
        <div class="grid gap-6 {{ $isManager ? 'lg:grid-cols-[360px_1fr]' : '' }}">
            
            <!-- MANAGER SIDEBAR: CREATE CLUB -->
            @if($isManager)
                <form wire:submit="createClub" class="h-fit rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs space-y-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Create a Club</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Form a new student club or extra-curricular group.</p>
                    </div>

                    <div class="space-y-3.5">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Club Name</label>
                            <input wire:model="clubName" type="text" placeholder="e.g. Debate Club" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-semibold text-slate-900 transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20">
                            @error('clubName') <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Club Color</label>
                            <input wire:model="clubColor" type="color" class="h-10 w-full cursor-pointer rounded-xl border border-slate-200 p-1">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Teacher Patron</label>
                            <select wire:model="clubPatronId" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-semibold text-slate-900 transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20">
                                <option value="">No patron assigned yet</option>
                                @foreach($staff as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }}{{ $member->job_title ? ' — '.$member->job_title : '' }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">
                                Maximum Members <span class="font-normal text-slate-400">(Optional)</span>
                            </label>
                            <input wire:model="clubMaximumMembers" type="number" min="1" placeholder="Unlimited" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-semibold text-slate-900 transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Description</label>
                            <textarea wire:model="clubDescription" rows="3" placeholder="Club objectives or activities..." class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-semibold text-slate-900 transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20"></textarea>
                        </div>

                        <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-slate-800 active:scale-95 shadow-2xs">
                            Create Club
                        </button>
                    </div>
                </form>
            @endif

            <!-- CLUBS & MEMBER SELECTION SECTION -->
            <section class="space-y-6">
                <!-- CLUB CARDS SELECTION -->
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($clubs as $club)
                        <button type="button" 
                                wire:click="$set('selectedClubId','{{ $club->id }}')" 
                                class="overflow-hidden rounded-2xl border text-left shadow-2xs transition-all duration-200 bg-white {{ (string)$selectedClubId===(string)$club->id ? 'ring-2 ring-amber-400 border-amber-400 shadow-md' : 'border-slate-200 hover:border-slate-300' }}">
                            <div class="h-2 w-full" style="background:{{ $club->color }}"></div>
                            <div class="p-4">
                                <div class="flex items-center justify-between gap-2">
                                    <b class="text-xs font-bold text-slate-900 truncate">{{ $club->name }}</b>
                                    <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700">
                                        {{ $club->members_count }}{{ $club->maximum_members ? '/'.$club->maximum_members : '' }}
                                    </span>
                                </div>
                                <p class="mt-1 text-[11px] font-medium text-slate-500">Patron: <span class="text-slate-800">{{ $club->patron_name ?: 'Not assigned' }}</span></p>
                            </div>
                        </button>
                    @endforeach
                </div>

                @if($selectedClubId)
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50 p-5">
                            <div><h3 class="font-bold text-slate-900">Current club members</h3><p class="text-xs text-slate-500">{{ $clubMembers->count() }} assigned students</p></div>
                            <a href="{{ route('students.activities.export',['type'=>'club','activity'=>$selectedClubId]) }}" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700">Export for Excel</a>
                        </div>
                        <div class="max-h-80 overflow-auto"><table class="w-full min-w-[560px] text-left text-xs"><thead class="sticky top-0 bg-slate-900 text-[10px] uppercase tracking-wider text-white"><tr><th class="px-5 py-3">Admission no.</th><th class="px-5 py-3">Student</th><th class="px-5 py-3">Class</th><th class="px-5 py-3">Gender</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($clubMembers as $member)<tr><td class="px-5 py-3 font-mono text-slate-500">{{ $member->admission_no }}</td><td class="px-5 py-3 font-bold text-slate-900">{{ $member->name }}</td><td class="px-5 py-3">{{ $member->class_name ?: '—' }}</td><td class="px-5 py-3 capitalize">{{ $member->gender ?: '—' }}</td></tr>@empty<tr><td colspan="4" class="p-8 text-center text-slate-400">No members assigned.</td></tr>@endforelse</tbody></table></div>
                    </div>
                @endif

                <!-- MEMBER ASSIGNMENT TABLE -->
                @if($clubs->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-300 p-12 text-center text-xs font-semibold text-slate-400">
                        No clubs available.
                    </div>
                @elseif($selectedClubId)
                    <form wire:submit="saveClubMembers" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/50 p-5">
                            <div>
                                <h2 class="text-base font-bold text-slate-900">Assign Club Members</h2>
                                <p class="text-xs text-slate-500">Membership is managed by school administrators and assigned patrons.</p>
                            </div>
                            <button type="submit" class="rounded-xl bg-amber-400 px-5 py-2 text-xs font-bold text-slate-950 transition hover:bg-amber-300 active:scale-95 shadow-2xs">
                                Save Membership Changes
                            </button>
                        </div>

                        @error('selectedStudents')
                            <div class="mx-5 mt-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs font-semibold text-rose-700">
                                {{ $message }}
                            </div>
                        @enderror

                        <!-- SEARCH & FILTERS -->
                        <div class="grid gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 bg-slate-50/30">
                            <input wire:model.live.debounce.300ms="studentSearch" 
                                   type="search" 
                                   placeholder="Search student name or admission number..." 
                                   class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-900 transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20">
                            <select wire:model.live="classFilter" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-900 transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20">
                                <option value="">All Classes</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- STUDENTS TABLE -->
                        <div class="max-h-[520px] overflow-y-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="sticky top-0 z-10 border-b border-slate-200 bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 shadow-2xs">
                                    <tr>
                                        <th class="px-5 py-3.5 w-12 text-center">Select</th>
                                        <th class="px-5 py-3.5">Student</th>
                                        <th class="px-5 py-3.5">Class</th>
                                        <th class="px-5 py-3.5">Gender</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($students as $student)
                                        <tr class="transition hover:bg-slate-50/80">
                                            <td class="px-5 py-3.5 text-center">
                                                <input wire:model="selectedStudents" 
                                                       value="{{ $student->id }}" 
                                                       type="checkbox" 
                                                       class="h-4 w-4 rounded border-slate-300 text-amber-500 transition focus:ring-amber-400">
                                            </td>
                                            <td class="px-5 py-3.5 font-bold text-slate-900">
                                                {{ $student->name }}
                                                <span class="block font-mono text-[11px] font-normal text-slate-400">{{ $student->admission_no }}</span>
                                            </td>
                                            <td class="px-5 py-3.5 font-semibold text-slate-600">
                                                {{ $student->schoolClass?->name ?: '—' }}
                                            </td>
                                            <td class="px-5 py-3.5 capitalize font-semibold text-slate-600">
                                                {{ $student->gender }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="p-12 text-center text-xs font-semibold text-slate-400">
                                                No students match the current filters.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>
                @endif
            </section>
        </div>
    @endif
</div>
