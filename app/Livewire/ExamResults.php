<?php

namespace App\Livewire;

use App\Models\Exam;
use App\Models\GradingScale;
use App\Models\Student;
use App\Services\StudentSubjectSelectionService;
use App\Support\TeacherAcademicScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ExamResults extends Component
{
    public string $examId = '';

    public function publish(): void
    {
        $exam = $this->selectedExamOrFail();
        abort_unless($this->canManage() && $exam->term->isOpen(), 403);

        $report = $this->calculate($exam);
        if (! $report['readiness']['ready']) {
            $this->addError('examId', 'Resolve the readiness issues before publishing these results.');
            return;
        }

        $exam->update([
            'published_at' => now(),
            'published_by' => Auth::id(),
            'status' => 'published',
        ]);
        session()->flash('status', 'Results published to eligible learners and parents.');
    }

    public function unpublish(): void
    {
        $exam = $this->selectedExamOrFail();
        abort_unless($this->canManage() && $exam->term->isOpen(), 403);

        $exam->update(['published_at' => null, 'published_by' => null, 'status' => 'draft']);
        session()->flash('status', 'Results are now internal only.');
    }

    protected function calculate(Exam $exam): array
    {
        return app(\App\Services\TeacherExamReport::class)->calculate($exam);
    }

    protected function selectedExamOrFail(): Exam
    {
        $exam = Exam::with(['schoolClass', 'stream', 'papers.subject', 'term'])
            ->where('school_id', Auth::user()->school_id)->findOrFail($this->examId);
        abort_unless(TeacherAcademicScope::canViewExam(Auth::user(), $exam->school_class_id, $exam->term_id), 403);
        return $exam;
    }

    protected function canManage(): bool
    {
        return ! TeacherAcademicScope::isTeacher(Auth::user()) && Auth::user()->hasPermission('exams.results');
    }

    public function render()
    {
        $school = Auth::user()->school;
        $term = $school->currentTerm();
        $exams = Exam::with(['schoolClass', 'stream'])->where('school_id', $school->id)->when($term, fn (Builder $query) => $query->where('term_id', $term->id))->orderByDesc('created_at')->get()
            ->filter(fn(Exam $exam)=>TeacherAcademicScope::canViewExam(Auth::user(),$exam->school_class_id,$exam->term_id))->values();
        $exam = $this->examId !== '' ? $this->selectedExamOrFail() : null;
        $report = $exam ? $this->calculate($exam) : ['papers' => collect(), 'results' => collect(), 'readiness' => null];

        return view('livewire.exam-results', compact('term', 'exams', 'exam') + $report + ['canManage' => $this->canManage(), 'pageTitle' => 'Exam Results']);
    }
}
