<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\ExamPaper;
use App\Models\Student;
use App\Services\StudentSubjectSelectionService;
use App\Support\TeacherAcademicScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class MarksEntry extends Component
{
    public string $paperId = '';

    public array $scores = [];

    public function updatedPaperId(): void
    {
        $this->resetValidation();
        $this->scores = [];

        if ($paper = $this->selectedPaper()) {
            $this->scores = DB::table('exam_marks')
                ->where('exam_paper_id', $paper->id)
                ->pluck('score', 'student_id')
                ->map(fn ($score) => $score === null ? null : (string) (float) $score)
                ->all();
        }
    }

    public function saveDraft(): void
    {
        $this->persist('draft');
    }

    public function submitForApproval(): void
    {
        $this->persist('submitted');
    }

    public function approve(): void
    {
        $paper = $this->selectedPaperOrFail();
        abort_unless($this->canApprove() && $paper->exam->term->isOpen(), 403);

        DB::transaction(function () use ($paper): void {
            $submission = DB::table('exam_paper_submissions')->where('exam_paper_id', $paper->id)->lockForUpdate()->first();
            if (($submission?->status ?? 'draft') !== 'submitted') {
                throw ValidationException::withMessages(['paperId' => 'Only submitted marks can be approved.']);
            }
            $students = $this->studentsFor($paper);
            $scored = DB::table('exam_marks')->where('exam_paper_id', $paper->id)->whereNotNull('score')->whereIn('student_id', $students->pluck('id'))->pluck('student_id');
            if ($students->isEmpty() || $students->pluck('id')->diff($scored)->isNotEmpty()) {
                throw ValidationException::withMessages(['paperId' => 'The class roster changed or some marks are missing. Return the paper to draft and complete it before approval.']);
            }
            DB::table('exam_paper_submissions')->where('exam_paper_id', $paper->id)->update([
                'status' => 'approved', 'approved_by' => Auth::id(), 'approved_at' => now(), 'updated_at' => now(),
            ]);
            AuditLog::record(Auth::user()->school_id, 'exam.marks.approved', $paper);
        });

        session()->flash('status', 'Marks approved and included in the examination results.');
    }

    public function reopen(): void
    {
        $paper = $this->selectedPaperOrFail();
        abort_unless($this->canApprove() && $paper->exam->term->isOpen(), 403);

        if ($paper->exam->isPublished()) {
            $this->addError('paperId', 'Unpublish the exam before reopening an approved paper.');

            return;
        }

        if (($this->submissionFor($paper)?->status ?? 'draft') !== 'approved') {
            $this->addError('paperId', 'Only approved papers can be reopened.');

            return;
        }

        DB::table('exam_paper_submissions')->where('exam_paper_id', $paper->id)->update([
            'status' => 'draft',
            'approved_by' => null,
            'approved_at' => null,
            'updated_at' => now(),
        ]);

        session()->flash('status', 'The paper was reopened for editing.');
    }

    protected function persist(string $status): void
    {
        $paper = $this->selectedPaperOrFail();
        abort_unless($paper->exam->term->isOpen() && $this->canEnter($paper), 403);

        if (($this->submissionFor($paper)?->status ?? 'draft') !== 'draft') {
            $this->addError('paperId', 'Submitted or approved marks are read only. An academic approver must reopen an approved paper before it can be edited.');

            return;
        }

        $students = $this->studentsFor($paper);
        $rules = [];

        foreach ($students as $student) {
            $rules['scores.'.$student->id] = [
                $status === 'submitted' ? 'required' : 'nullable',
                'numeric',
                'min:0',
                'max:'.$paper->maximum_score,
            ];
        }

        if ($rules) {
            $this->validate($rules, [
                'scores.*.required' => 'Enter a score for every learner before submitting.',
                'scores.*.numeric' => 'Every score must be a number.',
                'scores.*.min' => 'Scores cannot be negative.',
                'scores.*.max' => 'A score cannot exceed '.$paper->maximum_score.'.',
            ]);
        }

        DB::transaction(function () use ($paper, $students, $status): void {
            $now = now();
            $rows = $students->map(function (Student $student) use ($paper, $now) {
                $score = $this->scores[$student->id] ?? null;

                return ['exam_paper_id' => $paper->id, 'student_id' => $student->id, 'score' => $score === '' ? null : $score,
                    'entered_by' => Auth::id(), 'created_at' => $now, 'updated_at' => $now];
            })->all();
            if ($rows !== []) {
                DB::table('exam_marks')->upsert($rows, ['exam_paper_id', 'student_id'], ['score', 'entered_by', 'updated_at']);
            }

            DB::table('exam_paper_submissions')->upsert([[
                'exam_paper_id' => $paper->id, 'status' => $status,
                'submitted_by' => $status === 'submitted' ? Auth::id() : null,
                'submitted_at' => $status === 'submitted' ? $now : null,
                'approved_by' => null, 'approved_at' => null, 'created_at' => $now, 'updated_at' => $now,
            ]], ['exam_paper_id'], ['status', 'submitted_by', 'submitted_at', 'approved_by', 'approved_at', 'updated_at']);
            if ($status === 'submitted') {
                AuditLog::record(Auth::user()->school_id, 'exam.marks.submitted', $paper);
            }
        });

        session()->flash('status', $status === 'submitted'
            ? 'Marks submitted for academic approval.'
            : 'Draft marks saved.');
    }

    protected function availablePapers(): Collection
    {
        $user = Auth::user();
        $term = $user->school->currentTerm();

        if (! $term) {
            return collect();
        }

        return ExamPaper::with(['exam.schoolClass', 'exam.term', 'exam.stream', 'subject'])
            ->whereHas('exam', fn (Builder $query) => $query
                ->where('school_id', $user->school_id)
                ->where('term_id', $term->id))
            ->get()
            ->filter(fn (ExamPaper $paper) => $this->canAccess($paper))
            ->sortBy(fn (ExamPaper $paper) => implode('|', [
                $paper->exam->schoolClass->name,
                $paper->exam->name,
                $paper->subject->name,
            ]))
            ->values();
    }

    protected function selectedPaper(): ?ExamPaper
    {
        if ($this->paperId === '') {
            return null;
        }

        return ExamPaper::with(['exam.schoolClass', 'exam.stream', 'exam.term', 'subject'])
            ->whereHas('exam', fn (Builder $query) => $query
                ->where('school_id', Auth::user()->school_id))
            ->find($this->paperId);
    }

    protected function selectedPaperOrFail(): ExamPaper
    {
        return $this->selectedPaper() ?? abort(404);
    }

    protected function studentsFor(ExamPaper $paper): Collection
    {
        $query = Student::where('school_id', $paper->exam->school_id)
            ->where('school_class_id', $paper->exam->school_class_id)
            ->when($paper->exam->stream_id, fn (Builder $query, int $streamId) => $query->where('stream_id', $streamId))
            ->where('status', 'active')
            ->orderBy('name');

        return StudentSubjectSelectionService::constrainStudentsForSubject(
            $query,
            $paper->exam->schoolClass,
            $paper->exam->term_id,
            $paper->subject_id,
        )->get();
    }

    protected function submissionFor(ExamPaper $paper): ?object
    {
        return DB::table('exam_paper_submissions')->where('exam_paper_id', $paper->id)->first();
    }

    protected function canApprove(): bool
    {
        $user = Auth::user();

        return ! TeacherAcademicScope::isTeacher($user)
            && $user->hasPermission('exams.results');
    }

    protected function canEnter(ExamPaper $paper): bool
    {
        $user = Auth::user();

        if ($paper->exam->school_id !== $user->school_id) {
            return false;
        }

        return TeacherAcademicScope::canEnterPaper(
            $user, $paper->exam->school_class_id, $paper->subject_id, $paper->exam->term_id
        );
    }

    protected function canAccess(ExamPaper $paper): bool
    {
        return $paper->exam->school_id === Auth::user()->school_id
            && ($this->canEnter($paper) || $this->canApprove());
    }

    public function render()
    {
        $paper = $this->selectedPaper();
        abort_if($paper && ! $this->canAccess($paper), 403);
        $submission = $paper ? $this->submissionFor($paper) : null;

        return view('livewire.marks-entry', [
            'papers' => $this->availablePapers(),
            'paper' => $paper,
            'students' => $paper ? $this->studentsFor($paper) : collect(),
            'paperStatus' => $submission?->status ?? 'draft',
            'submission' => $submission,
            'canManage' => $this->canApprove(),
            'term' => Auth::user()->school->currentTerm(),
            'pageTitle' => 'Enter Marks',
        ]);
    }
}
