<div class="mx-auto max-w-5xl">
    <div class="mb-8">
        <h1 class="text-2xl font-bold">Results and report cards</h1>
        <p class="text-slate-500">View every published result for your linked learner account and preview the official report card.</p>
    </div>

    @if($students->isEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-amber-900">
            No learner account is linked to this login yet. Please ask the school registrar to link your account.
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            @forelse($students as $student)
                <section class="rounded-2xl border bg-white p-5 shadow-sm">
                    <h2 class="font-bold text-slate-900">{{ $student->name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $student->schoolClass?->name }} · {{ $student->admission_no }}</p>
                    <div class="mt-4 space-y-2">
                        @forelse($exams->where('school_class_id', $student->school_class_id)->filter(fn ($exam) => ! $exam->stream_id || $exam->stream_id === $student->stream_id) as $exam)
                            @php($blocked = $feeRule && $student->balance($exam->term) > 0)
                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 text-sm"><span><strong>{{ $exam->name }}</strong><br><span class="text-slate-500">{{ $exam->term->name }}, {{ $exam->term->year }}</span></span>@if($blocked)<span class="text-right text-xs font-bold text-rose-600">Fee clearance<br>required</span>@else<a href="{{ route('exams.report-card', [$exam, $student]) }}" target="_blank" class="rounded-lg bg-yellow-400 px-3 py-2 font-bold text-slate-900">Preview report</a>@endif</div>
                        @empty
                            <p class="text-sm text-slate-500">No published results are available for this learner yet.</p>
                        @endforelse
                    </div>
                </section>
            @empty @endforelse
        </div>
    @endif
</div>
