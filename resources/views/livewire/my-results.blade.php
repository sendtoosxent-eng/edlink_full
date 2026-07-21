<div class="mx-auto max-w-5xl">
    <div class="mb-8">
        <h1 class="text-2xl font-bold">My results</h1>
        <p class="text-slate-500">Only published results for your linked learner accounts are shown.</p>
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
                        @forelse($exams->where('school_class_id', $student->school_class_id) as $exam)
                            <a href="{{ route('exams.report-card', [$exam, $student]) }}" target="_blank" class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 text-sm hover:bg-yellow-50">
                                <span><strong>{{ $exam->name }}</strong><br><span class="text-slate-500">{{ $exam->term->name }}, {{ $exam->term->year }}</span></span>
                                <span class="font-bold text-yellow-700">View report</span>
                            </a>
                        @empty
                            <p class="text-sm text-slate-500">No published results are available for the current term.</p>
                        @endforelse
                    </div>
                </section>
            @empty @endforelse
        </div>
    @endif
</div>
