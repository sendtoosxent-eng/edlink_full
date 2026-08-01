<div class="space-y-6">
    
    <!-- Top Banner / Header -->
    <header class="relative overflow-hidden rounded-3xl bg-slate-900 p-6 sm:p-8 text-white shadow-xl">
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-amber-300 tracking-tight">
                    {{ $canApprove ? 'Leave Requests' : 'My Leave' }}
                </h1>
                <p class="mt-1 text-sm text-slate-400 max-w-xl">
                    {{ $canApprove ? 'Review staff applications, assign replacements, and manage work handovers.' : 'Apply for leave and track your application status.' }}
                </p>
            </div>

            <div class="flex items-center gap-3 self-start sm:self-center">
                <div class="relative">
                    <select wire:model.live="statusFilter" class="appearance-none rounded-xl border border-slate-700 bg-slate-800 py-2.5 pl-4 pr-10 text-sm font-medium text-white transition duration-200 hover:border-slate-600 focus:bg-slate-800 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-400/10">
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ambient decorative background glow -->
        <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-amber-400/10 blur-3xl pointer-events-none"></div>
    </header>

    <!-- Alert Banners -->
    @if (session('status'))
        <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm text-emerald-900 shadow-sm backdrop-blur-sm">
            <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <p class="font-medium">{{ session('status') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-sm text-rose-900 shadow-sm backdrop-blur-sm">
            <svg class="h-5 w-5 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <p class="font-medium">{{ session('error') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900 shadow-sm">
            <div class="flex items-center gap-2 font-bold text-rose-800">
                <svg class="h-5 w-5 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                Please correct the errors below:
            </div>
            <ul class="mt-2 list-disc pl-9 space-y-1 text-rose-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Main Content Layout -->
    <div class="grid gap-8 lg:grid-cols-3 items-start">
        
        <!-- Left: Apply Form Card -->
        <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">
                <h2 class="text-lg font-extrabold text-slate-900">New Leave Request</h2>
                <span class="h-2 w-2 rounded-full bg-amber-400"></span>
            </div>

            <form wire:submit="requestLeave" class="space-y-4">
                @if ($canApprove)
                    <div>
                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Staff Member</label>
                        <div class="relative">
                            <select wire:model="staffId" 
                                    class="w-full appearance-none rounded-xl border @error('staffId') border-rose-300 bg-rose-50/30 text-rose-900 focus:border-rose-500 focus:ring-rose-500/10 @else border-slate-200 bg-slate-50/50 text-slate-800 hover:bg-white focus:border-amber-500 focus:ring-amber-500/10 @enderror py-2.5 pl-3.5 pr-10 text-sm font-medium transition-all duration-200 focus:bg-white focus:outline-none focus:ring-4">
                                <option value="">Select staff member</option>
                                @foreach ($staff as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }}{{ $member->job_title ? ' — ' . $member->job_title : '' }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                        @error('staffId') <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ $message }}</p> @enderror
                    </div>
                @else
                    <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Applicant</span>
                        <p class="font-bold text-slate-900 mt-0.5">{{ auth()->user()->name }}</p>
                        <p class="text-xs font-medium text-slate-500">{{ auth()->user()->job_title ?? 'Staff Member' }}</p>
                    </div>
                @endif

                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Leave Type</label>
                    <div class="relative">
                        <select wire:model="type" 
                                class="w-full appearance-none rounded-xl border @error('type') border-rose-300 bg-rose-50/30 text-rose-900 focus:border-rose-500 focus:ring-rose-500/10 @else border-slate-200 bg-slate-50/50 text-slate-800 hover:bg-white focus:border-amber-500 focus:ring-amber-500/10 @enderror py-2.5 pl-3.5 pr-10 text-sm font-medium transition-all duration-200 focus:bg-white focus:outline-none focus:ring-4">
                            @foreach ($types as $leaveType)
                                <option value="{{ $leaveType }}">{{ $leaveType }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                    @error('type') <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Starts On</label>
                        <input wire:model="startsOn" 
                               type="date" 
                               class="w-full rounded-xl border @error('startsOn') border-rose-300 bg-rose-50/30 text-rose-900 focus:border-rose-500 focus:ring-rose-500/10 @else border-slate-200 bg-slate-50/50 text-slate-800 hover:bg-white focus:border-amber-500 focus:ring-amber-500/10 @enderror py-2.5 px-3.5 text-sm font-medium transition-all duration-200 focus:bg-white focus:outline-none focus:ring-4">
                        @error('startsOn') <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Ends On</label>
                        <input wire:model="endsOn" 
                               type="date" 
                               class="w-full rounded-xl border @error('endsOn') border-rose-300 bg-rose-50/30 text-rose-900 focus:border-rose-500 focus:ring-rose-500/10 @else border-slate-200 bg-slate-50/50 text-slate-800 hover:bg-white focus:border-amber-500 focus:ring-amber-500/10 @enderror py-2.5 px-3.5 text-sm font-medium transition-all duration-200 focus:bg-white focus:outline-none focus:ring-4">
                        @error('endsOn') <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                        Reason <span class="font-normal text-slate-400 lowercase">(optional)</span>
                    </label>
                    <textarea wire:model="reason" 
                              rows="3" 
                              placeholder="Brief explanation for leave request..." 
                              class="w-full rounded-xl border @error('reason') border-rose-300 bg-rose-50/30 text-rose-900 focus:border-rose-500 focus:ring-rose-500/10 @else border-slate-200 bg-slate-50/50 text-slate-800 hover:bg-white focus:border-amber-500 focus:ring-amber-500/10 @enderror p-3 text-sm transition-all duration-200 focus:bg-white focus:outline-none focus:ring-4 placeholder:text-slate-400 resize-none"></textarea>
                    @error('reason') <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ $message }}</p> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled" class="w-full rounded-xl bg-amber-400 hover:bg-amber-500 text-slate-950 font-bold py-3.5 transition shadow-sm hover:shadow active:scale-[0.99] flex items-center justify-center gap-2">
                    <span wire:loading.remove>Submit Request</span>
                    <span wire:loading class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-slate-950" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Submitting...
                    </span>
                </button>
            </form>
        </section>

        <!-- Right: Leave History Table Panel -->
        <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm lg:col-span-2">
            <div class="border-b border-slate-100 px-6 py-5 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900">Leave History</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Showing recent application activity</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-500 tracking-wider">
                        <tr>
                            <th class="px-6 py-3.5">Staff</th>
                            <th class="px-6 py-3.5">Leave Details</th>
                            <th class="px-6 py-3.5">Duration</th>
                            <th class="px-6 py-3.5">Status</th>
                            @if ($canApprove)
                                <th class="px-6 py-3.5 text-right">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse ($leaves as $leave)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-slate-700 text-xs">
                                            {{ strtoupper(substr($leave->staff?->name ?? 'U', 0, 2)) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900">{{ $leave->staff?->name ?? 'Unknown' }}</p>
                                            <span class="text-xs text-slate-400 font-normal">{{ $leave->staff?->job_title ?? 'Staff' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-800">{{ $leave->type }}</span>
                                    @if ($leave->reason)
                                        <p class="text-xs text-slate-400 mt-0.5 line-clamp-1 max-w-xs font-normal" title="{{ $leave->reason }}">
                                            "{{ $leave->reason }}"
                                        </p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-slate-800 font-semibold">{{ $leave->starts_on->format('d M Y') }}</div>
                                    <span class="text-xs text-slate-400 font-normal">to {{ $leave->ends_on->format('d M Y') }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold
                                        {{ $leave->status === 'approved' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : '' }}
                                        {{ $leave->status === 'rejected' ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20' : '' }}
                                        {{ $leave->status === 'pending' ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20' : '' }}">
                                        <span class="h-1.5 w-1.5 rounded-full 
                                            {{ $leave->status === 'approved' ? 'bg-emerald-500' : '' }}
                                            {{ $leave->status === 'rejected' ? 'bg-rose-500' : '' }}
                                            {{ $leave->status === 'pending' ? 'bg-amber-500' : '' }}"></span>
                                        {{ ucfirst($leave->status) }}
                                    </span>
                                    @if ($leave->approver)
                                        <span class="block text-[11px] text-slate-400 mt-1 font-normal">By {{ $leave->approver->name }}</span>
                                    @endif
                                </td>
                                @if ($canApprove)
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        @if ($leave->status === 'pending')
                                            <div class="inline-flex items-center gap-2">
                                                <button wire:click="approve({{ $leave->id }})" class="rounded-lg bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition">
                                                    Approve
                                                </button>
                                                <button wire:click="reject({{ $leave->id }})" class="rounded-lg bg-rose-50 hover:bg-rose-100 px-3 py-1.5 text-xs font-bold text-rose-700 transition">
                                                    Reject
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-400 font-normal">Completed</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canApprove ? 5 : 4 }}" class="px-6 py-12 text-center text-slate-400">
                                    <svg class="mx-auto h-8 w-8 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    No leave requests match your criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    </div>

    <!-- Handover Modal Overlay -->
    @if ($approvingLeaveId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" wire:click.self="cancelApproval">
            <form wire:submit="confirmApproval" class="w-full max-w-lg rounded-3xl bg-white p-6 sm:p-8 shadow-2xl border border-slate-100">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="inline-block rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700 mb-2">Work Handover Required</span>
                        <h2 class="text-xl font-bold text-slate-900">Reassign Responsibilities</h2>
                        <p class="mt-1 text-xs text-slate-500">Assign temporary staff to cover subjects and lessons before approving leave.</p>
                    </div>
                    <button type="button" wire:click="cancelApproval" class="rounded-full p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">&times;</button>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-2xl bg-blue-50/70 p-4 border border-blue-100 text-center">
                        <b class="text-3xl font-extrabold text-blue-700">{{ $handoverSubjectCount }}</b>
                        <span class="block text-xs font-semibold text-blue-600 mt-0.5">Subject Assignments</span>
                    </div>
                    <div class="rounded-2xl bg-violet-50/70 p-4 border border-violet-100 text-center">
                        <b class="text-3xl font-extrabold text-violet-700">{{ $handoverTimetableCount }}</b>
                        <span class="block text-xs font-semibold text-violet-600 mt-0.5">Timetable Lessons</span>
                    </div>
                </div>

                <div class="mt-5">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Replacement Staff</label>
                    <div class="relative">
                        <select wire:model="replacementStaffId" 
                                class="w-full appearance-none rounded-xl border @error('replacementStaffId') border-rose-300 bg-rose-50/30 text-rose-900 focus:border-rose-500 focus:ring-rose-500/10 @else border-slate-200 bg-slate-50/50 text-slate-800 hover:bg-white focus:border-amber-500 focus:ring-amber-500/10 @enderror py-2.5 pl-3.5 pr-10 text-sm font-medium transition-all duration-200 focus:bg-white focus:outline-none focus:ring-4">
                            <option value="">Choose replacement staff</option>
                            @foreach ($replacementStaff as $member)
                                <option value="{{ $member->id }}">{{ $member->name }}{{ $member->job_title ? ' — ' . $member->job_title : '' }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                    @error('replacementStaffId') 
                        <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600">
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            {{ $message }}
                        </p> 
                    @enderror
                </div>

                <div class="mt-5 rounded-2xl border border-amber-200/80 bg-amber-50/60 p-3.5 text-xs text-amber-900 leading-relaxed">
                    <strong>Handover Notice:</strong> Approving will reassign active classes and timetable slots to the selected staff member for auditing.
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="cancelApproval" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-emerald-600 hover:bg-emerald-700 px-5 py-2.5 text-xs font-bold text-white shadow-md transition disabled:opacity-60 flex items-center gap-2">
                        <span wire:loading.remove>Assign &amp; Approve</span>
                        <span wire:loading>Processing...</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

</div>