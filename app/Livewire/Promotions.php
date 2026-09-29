<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\StudentEnrolment;
use App\Models\Term;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Promotions extends Component
{
    public string $sourceTermId = '';

    public string $targetTermId = '';

    public string $loadedSourceTermId = '';

    public array $outcomes = [];

    public array $targetClasses = [];

    public function mount(): void
    {
        $schoolId = Auth::user()->school_id;
        $this->sourceTermId = (string) Term::where('school_id', $schoolId)->where('status', 'closed')
            ->latest('year')->latest('term_number')->value('id');
        $this->targetTermId = (string) Term::where('school_id', $schoolId)->whereIn('status', ['pending', 'open'])
            ->orderBy('year')->orderBy('term_number')->value('id');
    }

    public function updatedSourceTermId(): void
    {
        $this->resetLoadedLearners();
    }

    public function loadLearners(): void
    {
        $schoolId = Auth::user()->school_id;
        $this->validate([
            'sourceTermId' => ['required', Rule::exists('terms', 'id')->where('school_id', $schoolId)->where('status', 'closed')],
        ]);

        $this->resetLoadedLearners();
        $classes = SchoolClass::where('school_id', $schoolId)->orderBy('sort_order')->orderBy('name')->get()->values();
        $enrolments = StudentEnrolment::where([
            'school_id' => $schoolId, 'term_id' => $this->sourceTermId, 'status' => 'active',
        ])->get();

        foreach ($enrolments as $enrolment) {
            $this->outcomes[$enrolment->id] = $enrolment->promotion_outcome ?: 'promoted';
            $index = $classes->search(fn (SchoolClass $class) => $class->id === $enrolment->school_class_id);
            $this->targetClasses[$enrolment->id] = (string) ($classes->get($index === false ? 0 : $index + 1)?->id ?? $enrolment->school_class_id);
        }
        $this->loadedSourceTermId = $this->sourceTermId;
    }

    public function commit(): void
    {
        $school = Auth::user()->school;
        $this->validate([
            'sourceTermId' => ['required', Rule::exists('terms', 'id')->where('school_id', $school->id)->where('status', 'closed')],
            'targetTermId' => ['required', Rule::exists('terms', 'id')->where('school_id', $school->id)->whereIn('status', ['pending', 'open'])],
        ]);
        if ($this->loadedSourceTermId !== $this->sourceTermId) {
            $this->addError('sourceTermId', 'Load the learners again before committing promotion decisions.');

            return;
        }

        $from = Term::where('school_id', $school->id)->findOrFail($this->sourceTermId);
        $to = Term::where('school_id', $school->id)->findOrFail($this->targetTermId);
        if ([$to->year, $to->term_number] <= [$from->year, $from->term_number]) {
            $this->addError('targetTermId', 'The target term must come after the source term.');

            return;
        }

        $rows = StudentEnrolment::with('student')->where([
            'school_id' => $school->id, 'term_id' => $from->id, 'status' => 'active',
        ])->get();
        if ($rows->isEmpty()) {
            $this->addError('sourceTermId', 'There are no active learners to promote from this term.');

            return;
        }

        $classIds = SchoolClass::where('school_id', $school->id)->pluck('id')->map(fn ($id) => (int) $id);
        foreach ($rows as $enrolment) {
            $outcome = $this->outcomes[$enrolment->id] ?? null;
            if (! in_array($outcome, ['promoted', 'repeated', 'graduated', 'withdrawn'], true)) {
                $this->addError('outcomes.'.$enrolment->id, 'Choose a valid promotion outcome.');
            }
            if (! in_array($outcome, ['graduated', 'withdrawn'], true)
                && ! $classIds->contains((int) ($this->targetClasses[$enrolment->id] ?? 0))) {
                $this->addError('targetClasses.'.$enrolment->id, 'Choose a target class in this school.');
            }
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        DB::transaction(function () use ($school, $from, $to, $rows): void {
            foreach ($rows as $enrolment) {
                $outcome = $this->outcomes[$enrolment->id];
                $enrolment->update(['promotion_outcome' => $outcome]);
                if (in_array($outcome, ['graduated', 'withdrawn'], true)) {
                    continue;
                }

                $classId = (int) $this->targetClasses[$enrolment->id];
                $streamId = $classId === $enrolment->school_class_id ? $enrolment->stream_id : null;
                $fee = FeeStructure::where([
                    'school_id' => $school->id, 'term_id' => $to->id, 'school_class_id' => $classId,
                    'student_category_id' => $enrolment->student_category_id,
                ])->first();
                StudentEnrolment::updateOrCreate(
                    ['student_id' => $enrolment->student_id, 'term_id' => $to->id],
                    ['school_id' => $school->id, 'school_class_id' => $classId, 'stream_id' => $streamId,
                        'student_category_id' => $enrolment->student_category_id, 'fee_structure_id' => $fee?->id,
                        'base_fee_amount' => $fee?->amount ?? 0, 'status' => $to->isOpen() ? 'active' : 'pending',
                        'enrolled_at' => now()->toDateString()],
                );
                if ($to->isOpen()) {
                    $enrolment->student->update(['school_class_id' => $classId, 'stream_id' => $streamId, 'term_id' => $to->id]);
                }
            }
            AuditLog::record($school->id, 'term.promotions_committed', $from, [
                'target_term_id' => $to->id, 'learners' => $rows->count(),
            ]);
        });

        $this->resetLoadedLearners();
        session()->flash('status', 'Promotion decisions committed.');
    }

    private function resetLoadedLearners(): void
    {
        $this->loadedSourceTermId = '';
        $this->outcomes = [];
        $this->targetClasses = [];
        $this->resetValidation();
    }

    public function render()
    {
        $schoolId = Auth::user()->school_id;

        return view('livewire.promotions', [
            'terms' => Term::where('school_id', $schoolId)->latest('year')->latest('term_number')->get(),
            'classes' => SchoolClass::where('school_id', $schoolId)->orderBy('sort_order')->orderBy('name')->get(),
            'enrolments' => $this->loadedSourceTermId === $this->sourceTermId
                ? StudentEnrolment::with(['student', 'schoolClass'])->where([
                    'school_id' => $schoolId, 'term_id' => $this->sourceTermId, 'status' => 'active',
                ])->get()
                : collect(),
            'pageTitle' => 'Promotions',
        ]);
    }
}
