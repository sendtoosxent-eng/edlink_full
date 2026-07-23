<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div><h1 class="text-2xl font-bold text-slate-900">{{ $canApprove ? 'Leave requests' : 'My leave' }}</h1><p class="mt-1 text-sm text-slate-500">{{ $canApprove ? 'Review staff applications and coordinate work handovers.' : 'Apply for leave and track the school decision.' }}</p></div>
        <select wire:model.live="statusFilter" class="rounded-xl border-slate-200 text-sm"><option value="all">All statuses</option><option value="pending">Pending</option><option value="approved">Approved</option><option value="rejected">Rejected</option></select>
    </div>

    @if (session('status'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>@endif
    @if (session('error'))<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><p class="font-bold">Please correct the following:</p><ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-bold text-slate-900">New leave request</h2>
            <form wire:submit="requestLeave" class="mt-4 space-y-4">
                @if($canApprove)<label class="block text-sm font-medium">Staff member<select wire:model="staffId" class="mt-1 w-full rounded-xl border-slate-200"><option value="">Select staff</option>@foreach($staff as $member)<option value="{{ $member->id }}">{{ $member->name }}{{ $member->job_title ? ' — '. $member->job_title : '' }}</option>@endforeach</select></label>@else<div class="rounded-xl bg-slate-50 p-3 text-sm"><span class="text-xs font-bold uppercase text-slate-400">Applicant</span><p class="font-bold">{{ auth()->user()->name }}</p><p class="text-xs text-slate-500">{{ auth()->user()->job_title }}</p></div>@endif
                <label class="block text-sm font-medium">Leave type<select wire:model="type" class="mt-1 w-full rounded-xl border-slate-200">@foreach($types as $leaveType)<option value="{{ $leaveType }}">{{ $leaveType }}</option>@endforeach</select></label>
                <div class="grid grid-cols-2 gap-3"><label class="block text-sm font-medium">Starts<input wire:model="startsOn" type="date" class="mt-1 w-full rounded-xl border-slate-200"></label><label class="block text-sm font-medium">Ends<input wire:model="endsOn" type="date" class="mt-1 w-full rounded-xl border-slate-200"></label></div>
                <label class="block text-sm font-medium">Reason <span class="font-normal text-slate-400">(optional)</span><textarea wire:model="reason" rows="3" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Brief reason for leave"></textarea></label>
                <button class="w-full rounded-xl bg-yellow-400 px-4 py-3 text-sm font-bold text-slate-950 hover:bg-yellow-500">Submit request</button>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold text-slate-900">Leave history</h2></div>
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-slate-50 text-left text-slate-500"><tr><th class="px-4 py-3">Staff</th><th class="px-4 py-3">Leave</th><th class="px-4 py-3">Dates</th><th class="px-4 py-3">Status</th>@if($canApprove)<th class="px-4 py-3">Action</th>@endif</tr></thead><tbody class="divide-y divide-slate-100">@forelse($leaves as $leave)<tr><td class="px-4 py-3 font-medium">{{ $leave->staff?->name }}<span class="block text-xs font-normal text-slate-400">{{ $leave->staff?->job_title }}</span></td><td class="px-4 py-3">{{ $leave->type }}@if($leave->reason)<span class="block max-w-xs text-xs text-slate-400">{{ $leave->reason }}</span>@endif</td><td class="px-4 py-3 whitespace-nowrap">{{ $leave->starts_on->format('d M Y') }}<span class="block text-xs text-slate-400">to {{ $leave->ends_on->format('d M Y') }}</span></td><td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs font-bold {{ $leave->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($leave->status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst($leave->status) }}</span>@if($leave->approver)<span class="mt-1 block text-xs text-slate-400">by {{ $leave->approver->name }}</span>@endif</td>@if($canApprove)<td class="px-4 py-3">@if($leave->status === 'pending')<div class="flex gap-2"><button wire:click="approve({{ $leave->id }})" class="rounded-lg bg-emerald-600 px-2 py-1 text-xs font-bold text-white">Approve</button><button wire:click="reject({{ $leave->id }})" class="rounded-lg bg-rose-600 px-2 py-1 text-xs font-bold text-white">Reject</button></div>@else<span class="text-xs text-slate-400">Decision recorded</span>@endif</td>@endif</tr>@empty<tr><td colspan="{{ $canApprove ? 5 : 4 }}" class="px-4 py-10 text-center text-slate-500">No leave requests found.</td></tr>@endforelse</tbody></table></div>
        </section>
    </div>

@if($approvingLeaveId)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4" wire:click.self="cancelApproval">
    <form wire:submit="confirmApproval" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-start justify-between"><div><h2 class="text-xl font-bold">Assign work before approval</h2><p class="mt-1 text-sm text-slate-500">This teacher has active responsibilities that must be handed over before leave is confirmed.</p></div><button type="button" wire:click="cancelApproval" class="text-2xl text-slate-400">&times;</button></div>
        <div class="mt-5 grid grid-cols-2 gap-3"><div class="rounded-xl bg-blue-50 p-4 text-center"><b class="text-2xl text-blue-700">{{ $handoverSubjectCount }}</b><span class="block text-xs text-blue-600">Subject assignments</span></div><div class="rounded-xl bg-violet-50 p-4 text-center"><b class="text-2xl text-violet-700">{{ $handoverTimetableCount }}</b><span class="block text-xs text-violet-600">Timetable lessons</span></div></div>
        <label class="mt-5 block text-sm font-semibold">Assign all work to<select wire:model="replacementStaffId" class="mt-1.5 w-full rounded-xl border-slate-200"><option value="">Choose replacement staff</option>@foreach($replacementStaff as $member)<option value="{{ $member->id }}">{{ $member->name }}{{ $member->job_title ? ' — '. $member->job_title : '' }}</option>@endforeach</select></label>
        @error('replacementStaffId')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">Approval will transfer the current subject assignments and timetable lessons and record the complete handover for auditing.</div>
        <div class="mt-6 flex justify-end gap-3"><button type="button" wire:click="cancelApproval" class="rounded-xl border px-4 py-2 text-sm font-bold">Cancel</button><button wire:loading.attr="disabled" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white disabled:opacity-60"><span wire:loading.remove>Assign work & approve</span><span wire:loading>Processing...</span></button></div>
    </form>
</div>
@endif
</div>
