<div>
    @if($openStudentId && $canManageStudents)
        <script>document.addEventListener('livewire:init', () => Livewire.dispatch('edit-student', { studentId: {{ $openStudentId }} }));</script>
    @endif

    <div class="space-y-6">
        
        <!-- HEADER BLOCK -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-sm">
            <div class="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-amber-300">
                        All Students
                    </h1>
                    <p class="text-sm font-medium text-slate-400 mt-1 max-w-2xl">
                        Archive history safely. Mark students inactive instead of deleting them when they leave.
                    </p>
                </div>

                <!-- REGISTER STUDENT BUTTON (MOVED TO HEADER RIGHT) -->
                @if($canManageStudents)<a href="{{ route('students.register') }}"
                   wire:navigate 
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-extrabold px-5 py-3 shadow-md hover:shadow-lg transition-all duration-200 active:scale-95 text-sm shrink-0 self-start sm:self-center">
                    <svg class="w-4 h-4 stroke-[3]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Register Student</span>
                </a>@endif
            </div>

            <!-- Glowing Ambient Background Effect -->
            <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
        </div>

        <!-- FEEDBACK ALERTS -->
        @if (session('status'))
            <div class="flex items-center justify-between gap-3 bg-emerald-50 border border-emerald-200/80 text-emerald-900 text-sm rounded-2xl px-4 py-3.5 shadow-xs">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-semibold">{{ session('status') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        <!-- MAIN TABLE CONTAINER -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            
            <!-- TOOLBAR FILTERS -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 sm:p-5 bg-slate-50/60 border-b border-slate-200/80">
                <!-- Search Input -->
                <div class="relative w-full sm:w-80">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" 
                           wire:model.live.debounce.400ms="search" 
                           placeholder="Search name or admission no..."
                           class="w-full pl-10 pr-4 py-2 text-sm font-medium bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition placeholder:text-slate-400 text-slate-800 shadow-2xs">
                </div>
                
                <!-- Status Select Filter -->
                <div class="flex items-center gap-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider hidden sm:inline">Filter Status:</label>
                    <select wire:model.live="statusFilter" 
                            class="w-full sm:w-auto text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs cursor-pointer">
                        <option value="active">Active Enrolment</option>
                        <option value="inactive">Inactive / Alumni</option>
                        <option value="all">View All Records</option>
                    </select>
                </div>
            </div>

            <!-- TABLE LAYOUT -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left whitespace-nowrap">
                    <thead>
                        <tr class="bg-slate-900 text-white font-bold text-xs uppercase tracking-wider">
                            <th class="py-3.5 px-5">Student Profile</th>
                            <th class="py-3.5 px-5">Admission No.</th>
                            <th class="py-3.5 px-5">Class & Stream</th>
                            <th class="py-3.5 px-5">Category</th>
                            <th class="py-3.5 px-5">Assigned Fee</th>
                            <th class="py-3.5 px-5">Status</th>
                            <th class="py-3.5 px-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @forelse($students as $student)
                            <tr class="hover:bg-slate-50/80 transition-colors group">
                                
                                <!-- Student Profile -->
                                <td class="py-3.5 px-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center overflow-hidden shrink-0 shadow-2xs">
                                            @if($student->photoUrl())
                                                <img src="{{ $student->photoUrl() }}" class="w-full h-full object-cover" alt="{{ $student->name }}">
                                            @else
                                                <span class="text-xs font-black text-slate-600">{{ strtoupper(substr($student->name, 0, 1)) }}</span>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 group-hover:text-amber-600 transition-colors">
                                                {{ $student->name }}
                                            </div>
                                            <div class="text-[11px] text-slate-400 font-semibold sm:hidden">
                                                #{{ $student->admission_no }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Admission No -->
                                <td class="py-3.5 px-5">
                                    <span class="font-mono text-xs font-bold text-slate-700 bg-slate-100 border border-slate-200/80 px-2.5 py-1 rounded-lg inline-block">
                                        {{ $student->admission_no }}
                                    </span>
                                </td>
                                
                                <!-- Class / Stream -->
                                <td class="py-3.5 px-5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-slate-800">{{ $student->schoolClass->name ?? '—' }}</span>
                                        @if($student->stream)
                                            <span class="text-[11px] font-bold text-slate-500 px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200/60">
                                                {{ $student->stream->name }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                
                                <!-- Category -->
                                <td class="py-3.5 px-5">
                                    <span class="text-slate-600 font-semibold">{{ $student->category->name ?? '—' }}</span>
                                </td>
                                
                                <!-- Fee Amount -->
                                <td class="py-3.5 px-5 font-mono font-extrabold text-slate-900">
                                    @php $fee = $student->mappedFeeAmount(); @endphp
                                    @if($fee !== null)
                                        <span class="text-slate-400 font-sans text-xs">UGX</span> {{ number_format($fee) }}
                                    @else
                                        <span class="text-slate-400 font-normal font-sans">—</span>
                                    @endif
                                </td>
                                
                                <!-- Status Badge -->
                                <td class="py-3.5 px-5">
                                    @if($student->status === 'active')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 border border-emerald-200 text-emerald-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span>Active</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 border border-slate-200 text-slate-500">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            <span>Inactive</span>
                                        </span>
                                    @endif
                                </td>
                                
                                <!-- Actions -->
                                <td class="py-3.5 px-5 text-right">
                                    @if($canManageStudents)
                                    <div class="inline-flex items-center gap-2">
                                        <!-- Edit Button -->
                                        <button onclick="Livewire.dispatch('edit-student', { studentId: {{ $student->id }} })" 
                                                title="Edit Profile" 
                                                class="p-2 rounded-xl text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition-colors">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        
                                        <!-- Toggle Status Button -->
                                        <button wire:click="toggleStatus({{ $student->id }})" 
                                                wire:confirm="{{ $student->status === 'active' ? 'Mark this student as inactive?' : 'Mark this student as active again?' }}" 
                                                class="text-xs font-bold transition-all px-3 py-1.5 rounded-xl border shadow-2xs {{ $student->status === 'active' ? 'text-rose-600 bg-rose-50/50 border-rose-200/80 hover:bg-rose-100' : 'text-emerald-700 bg-emerald-50/50 border-emerald-200/80 hover:bg-emerald-100' }}">
                                            {{ $student->status === 'active' ? 'Deactivate' : 'Reactivate' }}
                                        </button>
                                    </div>
                                    @else
                                        <span class="text-xs font-semibold text-slate-400">View only</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <!-- Empty State -->
                            <tr>
                                <td colspan="7" class="py-12 px-6 text-center">
                                    <div class="flex flex-col items-center justify-center max-w-xs mx-auto space-y-2">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xl">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                        <p class="text-base font-bold text-slate-800">No Student Records Found</p>
                                        <p class="text-xs text-slate-500">Try tweaking your search term or status filter parameters.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION FOOTER -->
            @if($students->hasPages())
                <div class="p-4 sm:p-5 border-t border-slate-200/80 bg-slate-50/40">
                    {{ $students->links() }}
                </div>
            @endif
        </div>
        
    </div>
</div>
