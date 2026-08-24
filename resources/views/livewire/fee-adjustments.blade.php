<div class="space-y-6">
    <div class="rounded-3xl bg-[#252641] p-6 text-white shadow-md sm:p-8">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-violet-300">Finance approval screen</p>
                <h1 class="mt-2 text-2xl font-black text-amber-300 sm:text-3xl">Fee Adjustments</h1>
                <p class="mt-2 max-w-xl text-xs leading-5 text-slate-300">Review individual fee reductions submitted for the active term. A learner’s balance changes only after approval.</p>
            </div>
            <a href="{{ route('fee-payments.index') }}" wire:navigate class="self-start rounded-xl bg-amber-400 px-4 py-2.5 text-xs font-black text-slate-950 hover:bg-amber-300">← Back to Fee Payments</a>
        </div>
    </div>

    @if(session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-900">✓ {{ session('status') }}</div>@endif
    @if(session('error'))<div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-900">{{ session('error') }}</div>@endif

    <section class="rounded-2xl border-2 border-violet-300 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div><h2 class="text-lg font-black text-slate-900">Pending Approval</h2><p class="mt-1 text-xs text-slate-500">{{ $term ? $term->name.', '.$term->year : 'No active term' }} · {{ auth()->user()->school->name }}</p></div>
            <span class="self-start rounded-full bg-amber-100 px-3 py-1.5 text-xs font-black text-amber-800">{{ $pendingAdjustments->count() }} pending</span>
        </div>

        @if($pendingAdjustments->isEmpty())
            <div class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center"><h3 class="text-sm font-black text-slate-900">No fee adjustments are waiting for approval</h3><p class="mt-2 text-xs text-slate-500">New successful requests from this school and active term will appear here automatically.</p></div>
        @else
            <label class="mt-5 block text-xs font-bold text-slate-700">Review note <span class="font-normal text-slate-400">(optional)</span><input wire:model="reviewNotes" class="mt-1.5 w-full rounded-xl border-slate-200 text-xs" placeholder="Reason for approval or rejection"></label>
            <div class="mt-4 space-y-3">
                @foreach($pendingAdjustments as $adjustment)
                    <article class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div><h3 class="font-black text-slate-900">{{ $adjustment->student->name }}</h3><p class="mt-1 text-xs text-slate-500">{{ $adjustment->student->admission_no }} · {{ $adjustment->student->schoolClass?->name ?? 'Unassigned' }}</p><p class="mt-2 text-sm font-bold text-violet-700">{{ ucwords(str_replace('_', ' ', $adjustment->type)) }} · Deduction UGX {{ number_format($adjustment->amount) }}</p><p class="mt-1 text-xs text-slate-600">{{ $adjustment->reason }}</p><p class="mt-2 text-[10px] text-slate-400">Request #{{ $adjustment->id }} · Submitted by {{ $adjustment->requester?->name ?? 'Former user' }} · {{ $adjustment->created_at->diffForHumans() }}</p></div>
                            <div class="flex shrink-0 gap-2"><button wire:click="reviewAdjustment({{ $adjustment->id }}, 'approved')" wire:confirm="Approve this fee adjustment?" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white">Approve</button><button wire:click="reviewAdjustment({{ $adjustment->id }}, 'rejected')" wire:confirm="Reject this fee adjustment?" class="rounded-xl bg-rose-50 px-4 py-2.5 text-xs font-black text-rose-700">Reject</button></div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-black text-slate-900">Recent Decisions</h2>
        <div class="mt-4 divide-y divide-slate-100">
            @forelse($reviewedAdjustments as $adjustment)
                <div class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between"><div><b class="text-sm text-slate-900">{{ $adjustment->student->name }}</b><p class="text-xs text-slate-500">UGX {{ number_format($adjustment->amount) }} · Reviewed by {{ $adjustment->reviewer?->name ?? 'Former user' }}</p></div><span class="self-start rounded-full px-2.5 py-1 text-[10px] font-black {{ $adjustment->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">{{ strtoupper($adjustment->status) }}</span></div>
            @empty
                <p class="py-6 text-center text-xs text-slate-500">No adjustment decisions have been recorded for this term.</p>
            @endforelse
        </div>
    </section>
</div>
