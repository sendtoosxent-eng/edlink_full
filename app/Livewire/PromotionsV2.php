<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\StudentEnrolment;
use App\Models\Term;
use App\Services\StageTermReportCalculator;
use App\Services\GraduationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PromotionsV2 extends Component
{
    public string $sourceTermId = '';
    public string $targetTermId = '';
    public string $passMark = '50';
    public array $preview = [];
    public bool $previewReady = false;

    public function mount(): void
    {
        $school = Auth::user()->school;
        $this->sourceTermId = (string) Term::where('school_id', $school->id)->where('status', 'closed')->latest('year')->latest('id')->value('id');
        $this->targetTermId = (string) Term::where('school_id', $school->id)->whereIn('status', ['pending', 'open'])->orderBy('year')->orderBy('id')->value('id');
    }

    public function updatedSourceTermId(): void { $this->clearPreview(); }
    public function updatedTargetTermId(): void { $this->clearPreview(); }
    public function updatedPassMark(): void { $this->clearPreview(); }

    public function clearPreview(): void
    {
        $this->preview = [];
        $this->previewReady = false;
    }

    public function generatePreview(): void
    {
        $validated = $this->validate([
            'sourceTermId' => ['required', 'integer'],
            'targetTermId' => ['required', 'integer', 'different:sourceTermId'],
            'passMark' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $school = Auth::user()->school;
        $source = Term::where('school_id', $school->id)->findOrFail($validated['sourceTermId']);
        $target = Term::where('school_id', $school->id)->findOrFail($validated['targetTermId']);
        if ($source->status !== 'closed' || ! in_array($target->status, ['pending', 'open'], true)) {
            session()->flash('error', 'Choose a closed source term and a pending or open target term.');
            return;
        }
        if (! $source->canProgressTo($target)) {
            session()->flash('error', 'Select the next academic term in sequence: 1 to 2, 2 to 3, or Term 3 to next year\'s Term 1.');
            return;
        }

        $classes = SchoolClass::where('school_id', $school->id)->orderBy('sort_order')->orderBy('id')->get()->values();
        $graduatingClassId = $classes->firstWhere('is_graduating_class', true)?->id ?? $classes->last()?->id;
        $enrolments = StudentEnrolment::with(['student', 'schoolClass'])->where([
            'school_id' => $school->id, 'term_id' => $source->id, 'status' => 'active',
        ])->get();

        $this->preview = $enrolments->map(function (StudentEnrolment $enrolment) use ($source, $classes, $graduatingClassId): array {
            $report = StageTermReportCalculator::student($enrolment->student, $source);
            $passed = $report['marks']->isNotEmpty() && $report['average'] >= (float) $this->passMark;
            $index = $classes->search(fn (SchoolClass $class) => $class->id === $enrolment->school_class_id);
            $nextClass = $index === false ? null : $classes->get($index + 1);
            if (! $source->isFinalTerm()) {
                $outcome = 'continued';
                $targetClass = $enrolment->schoolClass;
            } else {
                $outcome = $passed ? ($enrolment->school_class_id === $graduatingClassId ? 'graduated' : ($nextClass ? 'promoted' : 'graduated')) : 'repeated';
                $targetClass = $outcome === 'promoted' ? $nextClass : ($outcome === 'repeated' ? $enrolment->schoolClass : null);
            }

            return [
                'enrolment_id' => $enrolment->id,
                'student_id' => $enrolment->student_id,
                'student_name' => $enrolment->student->name,
                'admission_no' => $enrolment->student->admission_no,
                'current_class' => $enrolment->schoolClass?->name ?? 'Unassigned',
                'average' => $report['average'],
                'subjects' => $report['marks']->count(),
                'passed' => $passed,
                'outcome' => $outcome,
                'target_class_id' => $targetClass?->id,
                'target_class' => $targetClass?->name ?? 'Leaves school',
            ];
        })->all();

        $this->previewReady = true;
        session()->flash('status', count($this->preview).' learners evaluated. Review the preview before confirmation.');
    }

    public function commit(): void
    {
        if (! $this->previewReady || $this->preview === []) {
            session()->flash('error', 'Generate and review a promotion preview first.');
            return;
        }

        $school = Auth::user()->school;
        $source = Term::where('school_id', $school->id)->findOrFail($this->sourceTermId);
        $target = Term::where('school_id', $school->id)->findOrFail($this->targetTermId);
        if ($source->status !== 'closed' || ! in_array($target->status, ['pending', 'open'], true)) {
            session()->flash('error', 'The term status changed. Generate a new preview.');
            $this->clearPreview();
            return;
        }
        if (! $source->canProgressTo($target)) {
            session()->flash('error', 'The target is no longer the next valid academic term. Generate a new preview.');
            $this->clearPreview();
            return;
        }

        $rows = StudentEnrolment::with('student')->where('school_id', $school->id)->where('term_id', $source->id)
            ->where('status', 'active')->whereIn('id', collect($this->preview)->pluck('enrolment_id'))->get()->keyBy('id');
        if ($rows->count() !== count($this->preview)) {
            session()->flash('error', 'Learner enrolments changed after preview. Generate a new preview.');
            $this->clearPreview();
            return;
        }

        DB::transaction(function () use ($school, $source, $target, $rows): void {
            foreach ($this->preview as $decision) {
                $enrolment = $rows->get($decision['enrolment_id']);
                $outcome = $decision['outcome'];
                if ($outcome === 'graduated') {
                    StudentEnrolment::where('student_id', $enrolment->student_id)->where('term_id', $target->id)->delete();
                    app(GraduationService::class)->graduate($enrolment, $source, (float) $decision['average']);
                    continue;
                }
                $enrolment->update(['promotion_outcome' => $outcome]);

                $classId = (int) $decision['target_class_id'];
                $fee = FeeStructure::where([
                    'school_id' => $school->id, 'term_id' => $target->id,
                    'school_class_id' => $classId, 'student_category_id' => $enrolment->student_category_id,
                ])->first();
                $sameClass = $classId === $enrolment->school_class_id;
                StudentEnrolment::updateOrCreate(
                    ['student_id' => $enrolment->student_id, 'term_id' => $target->id],
                    ['school_id' => $school->id, 'school_class_id' => $classId, 'stream_id' => $sameClass ? $enrolment->stream_id : null,
                        'student_category_id' => $enrolment->student_category_id, 'fee_structure_id' => $fee?->id,
                        'base_fee_amount' => $fee?->amount ?? 0, 'status' => $target->isOpen() ? 'active' : 'pending',
                        'enrolled_at' => now()->toDateString()],
                );
                if ($target->isOpen()) {
                    $enrolment->student->update(['school_class_id' => $classId, 'stream_id' => $sameClass ? $enrolment->stream_id : null, 'term_id' => $target->id, 'status' => 'active']);
                }
            }
            AuditLog::record($school->id, 'term.automatic_promotions_committed', $source, [
                'target_term_id' => $target->id, 'pass_mark' => (float) $this->passMark,
                'learners' => count($this->preview),
                'promoted' => collect($this->preview)->where('outcome', 'promoted')->count(),
                'repeated' => collect($this->preview)->where('outcome', 'repeated')->count(),
                'continued' => collect($this->preview)->where('outcome', 'continued')->count(),
                'graduated' => collect($this->preview)->where('outcome', 'graduated')->count(),
            ]);
        });

        $count = count($this->preview);
        $this->clearPreview();
        session()->flash('status', "Automatic promotion completed for {$count} learners.");
    }

    public function render()
    {
        $school = Auth::user()->school;
        return view('livewire.promotions-v2', [
            'terms' => Term::where('school_id', $school->id)->latest('year')->latest('id')->get(),
            'pageTitle' => 'Automatic Promotions',
        ]);
    }
}
