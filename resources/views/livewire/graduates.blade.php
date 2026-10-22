<div class="space-y-6">
    <header class="rounded-3xl bg-slate-900 p-6 text-white shadow-xl"><h1 class="text-2xl font-bold tracking-tight text-amber-300">Graduates & Alumni</h1><p class="text-sm text-slate-400 mt-2 leading-relaxed">Permanent completion records, final balances and certificates.</p></header>
    @if(session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</div>@endif
    @if($resultId)
    <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5" wire:key="external-results-{{ $resultId }}" x-init="$el.scrollIntoView({ behavior: 'smooth', block: 'start' })">
        <h2 class="text-lg font-black text-slate-900">External examination results - {{ $resultStudent->name }}</h2>
        <p class="mt-1 text-xs text-slate-600">Enter results as shown on the official statement. Saved subject results and overall outcomes appear on the recommendation letter.</p>
        <form wire:submit="saveExternalResult" class="mt-5 space-y-5">
            <div class="grid gap-3 sm:grid-cols-3">
                <label class="text-xs font-bold text-slate-700">Examination<select wire:model="externalResultType" class="mt-1 w-full rounded-xl border-slate-200">@foreach(['PLE','UCE','UACE','Other'] as $type)<option value="{{ $type }}">{{ $type }}</option>@endforeach</select></label>
                <label class="text-xs font-bold text-slate-700">Exam year<input type="number" wire:model="resultSummary.exam_year" class="mt-1 w-full rounded-xl border-slate-200"></label>
                <label class="text-xs font-bold text-slate-700">Candidate / index number<input wire:model="resultSummary.index_number" class="mt-1 w-full rounded-xl border-slate-200"></label>
            </div>
            <div class="space-y-3">
                @foreach($subjectResults as $index => $row)
                <div wire:key="result-subject-{{ $resultId }}-{{ $index }}" class="grid gap-3 rounded-xl border border-amber-200 bg-white p-3 sm:grid-cols-12">
                    <label class="text-xs font-bold text-slate-700 sm:col-span-4">Subject<select wire:model="subjectResults.{{ $index }}.subject_id" class="mt-1 w-full rounded-lg border-slate-200"><option value="">Choose subject</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select>@error('subjectResults.'.$index.'.subject_id')<span class="mt-1 block text-rose-700">{{ $message }}</span>@enderror</label>
                    <label class="text-xs font-bold text-slate-700 sm:col-span-2">Mark / 100<input type="number" min="0" max="100" step="0.01" wire:model="subjectResults.{{ $index }}.mark" class="mt-1 w-full rounded-lg border-slate-200">@error('subjectResults.'.$index.'.mark')<span class="mt-1 block text-rose-700">{{ $message }}</span>@enderror</label>
                    <label class="text-xs font-bold text-slate-700 sm:col-span-2">Grade<input wire:model="subjectResults.{{ $index }}.grade" placeholder="e.g. A or D1" class="mt-1 w-full rounded-lg border-slate-200">@error('subjectResults.'.$index.'.grade')<span class="mt-1 block text-rose-700">{{ $message }}</span>@enderror</label>
                    <label class="text-xs font-bold text-slate-700 sm:col-span-2">Aggregate / points<input type="number" min="0" step="0.01" wire:model="subjectResults.{{ $index }}.aggregate" class="mt-1 w-full rounded-lg border-slate-200">@error('subjectResults.'.$index.'.aggregate')<span class="mt-1 block text-rose-700">{{ $message }}</span>@enderror</label>
                    <button type="button" wire:click="removeResultSubject({{ $index }})" class="self-end rounded-lg px-2 py-3 text-xs font-bold text-rose-700 sm:col-span-2" aria-label="Remove subject {{ $index + 1 }}">Remove</button>
                </div>
                @endforeach
                <button type="button" wire:click="addResultSubject" class="rounded-lg border border-amber-300 bg-white px-4 py-2 text-xs font-bold text-slate-800">+ Add subject</button>
                <p class="text-xs text-slate-500">Marks and grades may be entered separately. Enter the official total below; it is not calculated automatically from all subjects.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <label class="text-xs font-bold text-slate-700">Total aggregate<input type="number" min="0" step="0.01" wire:model="resultSummary.total_aggregate" class="mt-1 w-full rounded-xl border-slate-200"></label>
                <label class="text-xs font-bold text-slate-700">Total points<input type="number" min="0" step="0.01" wire:model="resultSummary.total_points" class="mt-1 w-full rounded-xl border-slate-200"></label>
                <label class="text-xs font-bold text-slate-700">Division<input wire:model="resultSummary.division" placeholder="e.g. Division 1" class="mt-1 w-full rounded-xl border-slate-200"></label>
                <label class="text-xs font-bold text-slate-700">Overall result<input wire:model="resultSummary.overall_result" placeholder="As on the statement" class="mt-1 w-full rounded-xl border-slate-200"></label>
            </div>
            <label class="block text-xs font-bold text-slate-700">Result remarks<textarea wire:model="resultSummary.remarks" rows="2" class="mt-1 w-full rounded-xl border-slate-200"></textarea></label>
            @if($errors->any())<div role="alert" class="rounded-xl bg-rose-50 p-3 text-xs text-rose-700"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <div class="flex gap-3"><button type="submit" wire:loading.attr="disabled" wire:target="saveExternalResult" class="rounded-xl bg-amber-400 px-4 py-2 font-bold text-slate-950">Save external results</button><button type="button" wire:click="$set('resultId', null)" class="px-3 py-2 font-bold text-slate-600">Cancel</button></div>
        </form>
    </section>
    @endif
    <section class="rounded-3xl border bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b p-5 sm:flex-row"><input wire:model.live.debounce.300ms="search" placeholder="Search graduate or admission number" class="w-full rounded-xl border-slate-200 sm:max-w-md"><select wire:model.live="year" class="rounded-xl border-slate-200"><option value="">All years</option>@foreach($years as $item)<option value="{{ $item }}">{{ $item }}</option>@endforeach</select></div>
        <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="p-4">Graduate</th><th>Final class</th><th>Completed</th><th>Average</th><th>External results</th><th>Balance at exit</th><th>Certificate</th><th>Actions</th></tr></thead><tbody>
        @forelse($graduates as $record)<tr class="border-t"><td class="p-4"><strong>{{ $record->student->name }}</strong><div class="text-xs text-slate-500">{{ $record->student->admission_no }}</div></td><td>{{ $record->schoolClass->name }}</td><td>{{ $record->graduated_at->format('d M Y') }}</td><td>{{ number_format($record->final_average,1) }}%</td><td class="p-3 text-xs">@if($record->external_result_entered_at)<strong>{{ $record->external_result_type }}</strong><div>{{ $record->externalResults()['overall_result'] ?? $record->externalResults()['division'] ?? 'Results recorded' }}</div>@if(isset($record->externalResults()['total_aggregate']))<div>Aggregate: {{ $record->externalResults()['total_aggregate'] }}</div>@endif@else<span class="text-slate-400">Not entered</span>@endif</td><td>UGX {{ number_format($record->outstanding_balance,2) }}</td><td class="font-mono text-xs">{{ $record->certificate_number }}</td><td><div class="flex flex-wrap gap-2"><a href="{{ route('graduates.certificate',$record) }}" class="rounded-lg bg-amber-100 px-2.5 py-1.5 font-bold text-amber-800">Certificate</a><a href="{{ route('graduates.recommendation',$record) }}" class="rounded-lg bg-slate-100 px-2.5 py-1.5 font-bold text-slate-700">Recommendation</a>@if($canManage)<button wire:click="beginExternalResult({{ $record->id }})" class="rounded-lg bg-blue-50 px-2.5 py-1.5 font-bold text-blue-700">{{ $record->external_result_entered_at ? 'Edit results' : 'Enter results' }}</button><button wire:click="beginReversal({{ $record->id }})" class="px-2 py-1.5 font-bold text-rose-700">Reverse</button>@endif</div></td></tr>
        @empty<tr><td colspan="8" class="p-10 text-center text-slate-500">No graduation records found.</td></tr>@endforelse
        </tbody></table></div><div class="border-t p-4">{{ $graduates->links() }}</div>
    </section>
    @if($reversingId)<section class="rounded-2xl border border-rose-200 bg-rose-50 p-5"><h2 class="font-black text-rose-900">Reverse graduation</h2><p class="mt-1 text-xs text-rose-700">This restores the learner and final-term enrolment to active status. The reversal remains audited.</p><div class="mt-3 flex gap-2"><input wire:model="reversalReason" placeholder="Required reason (at least 8 characters)" class="flex-1 rounded-xl border-rose-200"><button wire:click="reverse" class="rounded-xl bg-rose-700 px-4 py-2 font-bold text-white">Confirm reversal</button><button wire:click="$set('reversingId', null)" class="px-3">Cancel</button></div>@error('reversalReason')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror</section>@endif
</div>
