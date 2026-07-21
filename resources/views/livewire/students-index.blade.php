<div>
    @if($openStudentId)
        <script>document.addEventListener('livewire:init', () => Livewire.dispatch('edit-student', { studentId: {{ $openStudentId }} }));</script>
    @endif
    <!-- Header Block -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">All Students</h1>
            <p class="text-sm text-slate-500 mt-1">Archive history safely. Mark students inactive instead of deleting them when they leave.</p>
        </div>
        <a href="{{ route('students.register') }}" wire:navigate class="inline-flex items-center justify-center gap-2 bg-yellow-500 hover:bg-yellow-400 active:bg-yellow-600 text-slate-950 font-semibold px-5 py-2.5 rounded-xl transition shadow-sm shadow-yellow-500/10 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            <span>Register Student</span>
        </a>
    </div>

    <!-- Feedback Alerts -->
    @if (session('status'))
        <div class="flex items-center gap-3 bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm rounded-xl px-4 py-3.5 mb-6 shadow-sm">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-medium">{{ session('status') }}</span>
        </div>
    @endif

    <!-- Main Content Container -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        
        <!-- Table Toolbar Filters -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-5 bg-slate-50/50 border-b border-slate-100">
            <div class="relative w-full sm:w-80">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search name or admission number..."
                    class="w-full pl-10 pr-4 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 transition shadow-sm placeholder:text-slate-400">
            </div>
            
            <div class="flex items-center gap-2">
                <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider hidden sm:inline">Status:</label>
                <select wire:model.live="statusFilter" class="w-full sm:w-auto text-sm bg-white border border-slate-200 rounded-xl px-3 py-2 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 transition shadow-sm">
                    <option value="active">Active Enrolment</option>
                    <option value="inactive">Inactive / Alumni</option>
                    <option value="all">View All Records</option>
                </select>
            </div>
        </div>

        <!-- Desktop Table Layout -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left whitespace-nowrap">
                <thead>
                    <tr class="text-slate-400 text-xs font-semibold uppercase tracking-wider border-b border-slate-100 bg-slate-50/20">
                        <th class="px-6 py-4">Student Profile</th>
                        <th class="px-6 py-4">Admission No.</th>
                        <th class="px-6 py-4">Class & Stream</th>
                        <th class="px-6 py-4">Category</th>
                        <th class="px-6 py-4">Assigned Fee</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($students as $student)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <!-- Student Profile -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center overflow-hidden shrink-0 border border-slate-200 shadow-inner">
                                        @if($student->photoUrl())
                                            <img src="{{ $student->photoUrl() }}" class="w-full h-full object-cover" alt="{{ $student->name }}">
                                        @else
                                            <span class="text-sm font-bold text-slate-600">{{ strtoupper(substr($student->name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div class="font-semibold text-slate-900 group-hover:text-yellow-600 transition-colors">{{ $student->name }}</div>
                                </div>
                            </td>
                            
                            <!-- Admission No -->
                            <td class="px-6 py-4 font-mono text-xs font-medium text-slate-600 bg-slate-50/30 px-2 py-1 rounded border border-slate-100 inline-block my-3 ml-6">{{ $student->admission_no }}</td>
                            
                            <!-- Class / Stream -->
                            <td class="px-6 py-4 text-slate-600 font-medium">
                                {{ $student->schoolClass->name ?? '—' }}
                                @if($student->stream)
                                    <span class="text-xs text-slate-400 font-normal px-1.5 py-0.5 rounded bg-slate-100 ml-1">{{ $student->stream->name }}</span>
                                @endif
                            </td>
                            
                            <!-- Category -->
                            <td class="px-6 py-4 text-slate-600">{{ $student->category->name ?? '—' }}</td>
                            
                            <!-- Fee Amount -->
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                @php $fee = $student->mappedFeeAmount(); @endphp
                                {{ $fee !== null ? 'UGX '.number_format($fee) : '—' }}
                            </td>
                            
                            <!-- Status Badges -->
                            <td class="px-6 py-4">
                                @if($student->status === 'active')
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-500 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            
                            <!-- Interactive Actions Row -->
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-3">
                                    <button onclick="Livewire.dispatch('edit-student', { studentId: {{ $student->id }} })" 
                                        title="Edit Profile" 
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    
                                    <button wire:click="toggleStatus({{ $student->id }})" 
                                        wire:confirm="{{ $student->status === 'active' ? 'Mark this student as inactive?' : 'Mark this student as active again?' }}" 
                                        class="text-xs font-semibold transition px-2.5 py-1 rounded-lg border {{ $student->status === 'active' ? 'text-rose-600 border-rose-100 hover:bg-rose-50' : 'text-emerald-600 border-emerald-100 hover:bg-emerald-50' }}">
                                        {{ $student->status === 'active' ? 'Deactivate' : 'Reactivate' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center max-w-xs mx-auto">
                                    <svg class="w-8 h-8 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <p class="text-sm font-semibold text-slate-700">No student records found</p>
                                    <p class="text-xs text-slate-400 mt-1">Try tweaking your configuration search parameters or status filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar Footer -->
        @if($students->hasPages())
            <div class="p-5 border-t border-slate-100 bg-slate-50/30">
                {{ $students->links() }}
            </div>
        @endif
    </div>
</div>
