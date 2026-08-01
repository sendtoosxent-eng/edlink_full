<?php

namespace App\Livewire;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\StudentSubjectSelectionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class StudentSubjectSelections extends Component
{
    public string $classId = '';
    public string $studentId = '';
    public array $selections = [];

    public function mount(): void
    {
        $this->classId = (string) ($this->eligibleClasses()->first()?->id ?? '');
        $this->selectFirstStudent();
    }

    public function updatedClassId(): void
    {
        $this->studentId = '';
        $this->selections = [];
        $this->resetValidation();
        $this->selectFirstStudent();
    }

    public function updatedStudentId(): void
    {
        $this->resetValidation();
        $this->loadSelections();
    }

    public function save(): void
    {
        $user = Auth::user();
        $term = $user->school->currentTerm();

        if (! $term || ! $term->isOpen()) {
            $this->addError('studentId', 'Open a school term before managing subject selections.');
            return;
        }

        $class = $this->eligibleClasses()->firstWhere('id', (int) $this->classId);
        $student = $this->students()->firstWhere('id', (int) $this->studentId);

        if (! $class || ! $student) {
            $this->addError('studentId', 'Select an eligible learner.');
            return;
        }

        $allowedTypes = array_keys(StudentSubjectSelectionService::typesFor($class));
        $offeredSubjectIds = $this->subjects()->pluck('id')->map(fn ($id) => (int) $id);
        $selected = collect($this->selections)
            ->filter(fn ($type) => in_array($type, $allowedTypes, true))
            ->mapWithKeys(fn ($type, $subjectId) => [(int) $subjectId => $type])
            ->filter(fn ($type, $subjectId) => $offeredSubjectIds->contains($subjectId));

        if ($selected->isEmpty()) {
            $this->addError('selections', 'Select at least one subject for this learner.');
            return;
        }

        if ($class->education_stage === 'advanced_level') {
            $principalCount = $selected->filter(fn ($type) => $type === 'principal')->count();
            $subsidiaryCount = $selected->filter(fn ($type) => $type === 'subsidiary')->count();

            if ($principalCount !== 3) {
                $this->addError('selections', 'An A-Level learner must have exactly three principal subjects.');
                return;
            }

            if ($subsidiaryCount < 1) {
                $this->addError('selections', 'An A-Level learner must have at least one subsidiary subject.');
                return;
            }
        }

        DB::transaction(function () use ($user, $term, $student, $selected): void {
            DB::table('student_subject_selections')
                ->where('school_id', $user->school_id)
                ->where('term_id', $term->id)
                ->where('student_id', $student->id)
                ->delete();

            $now = now();
            DB::table('student_subject_selections')->insert(
                $selected->map(fn ($type, $subjectId) => [
                    'school_id' => $user->school_id,
                    'term_id' => $term->id,
                    'student_id' => $student->id,
                    'subject_id' => $subjectId,
                    'selection_type' => $type,
                    'selected_by' => $user->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->values()->all(),
            );
        });

        $this->selections = $selected->mapWithKeys(fn ($type, $subjectId) => [(string) $subjectId => $type])->all();
        session()->flash('status', $student->name."'s subject selection was saved.");
    }

    protected function eligibleClasses(): Collection
    {
        return SchoolClass::where('school_id', Auth::user()->school_id)
            ->where(function ($query) {
                $query->where('education_stage', 'advanced_level')
                    ->orWhere(function ($lower) {
                        $lower->where('education_stage', 'lower_secondary')
                            ->whereIn('sort_order', [3, 4]);
                    });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    protected function selectedClass(): ?SchoolClass
    {
        return $this->eligibleClasses()->firstWhere('id', (int) $this->classId);
    }

    protected function students(): Collection
    {
        if ($this->classId === '') {
            return collect();
        }

        return Student::where('school_id', Auth::user()->school_id)
            ->where('school_class_id', $this->classId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    protected function subjects(): Collection
    {
        $term = Auth::user()->school->currentTerm();
        if (! $term || $this->classId === '') {
            return collect();
        }

        return DB::table('class_subjects')
            ->join('subjects', 'subjects.id', '=', 'class_subjects.subject_id')
            ->where('class_subjects.school_id', Auth::user()->school_id)
            ->where('class_subjects.term_id', $term->id)
            ->where('class_subjects.school_class_id', $this->classId)
            ->orderBy('subjects.name')
            ->get(['subjects.id', 'subjects.name', 'subjects.code']);
    }

    protected function selectFirstStudent(): void
    {
        $this->studentId = (string) ($this->students()->first()?->id ?? '');
        $this->loadSelections();
    }

    protected function loadSelections(): void
    {
        $term = Auth::user()->school->currentTerm();
        if (! $term || $this->studentId === '') {
            $this->selections = [];
            return;
        }

        $this->selections = DB::table('student_subject_selections')
            ->where('school_id', Auth::user()->school_id)
            ->where('term_id', $term->id)
            ->where('student_id', $this->studentId)
            ->pluck('selection_type', 'subject_id')
            ->mapWithKeys(fn ($type, $subjectId) => [(string) $subjectId => $type])
            ->all();
    }

    public function render()
    {
        $class = $this->selectedClass();
        $students = $this->students();
        $subjects = $this->subjects();
        $term = Auth::user()->school->currentTerm();
        $types = $class ? StudentSubjectSelectionService::typesFor($class) : [];
        $configuredStudentIds = $term && $this->classId !== ''
            ? DB::table('student_subject_selections')
                ->where('school_id', Auth::user()->school_id)
                ->where('term_id', $term->id)
                ->whereIn('student_id', $students->pluck('id'))
                ->distinct()
                ->pluck('student_id')
            : collect();

        return view('livewire.student-subject-selections', [
            'classes' => $this->eligibleClasses(),
            'class' => $class,
            'students' => $students,
            'student' => $students->firstWhere('id', (int) $this->studentId),
            'subjects' => $subjects,
            'types' => $types,
            'term' => $term,
            'configuredStudentIds' => $configuredStudentIds,
            'pageTitle' => 'Student Subject Selection',
        ]);
    }
}
