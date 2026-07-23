<?php

namespace App\Livewire;

use App\Models\Exam;
use App\Models\GradingScale;
use App\Models\Student;
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
        $papers = $exam->papers;
        $paperIds = $papers->pluck('id');
        $submissions = DB::table('exam_paper_submissions')->whereIn('exam_paper_id', $paperIds)->pluck('status', 'exam_paper_id');
        $approvedPapers = $papers->filter(fn ($paper) => ($submissions[$paper->id] ?? null) === 'approved')->values();
        $students = $this->studentsFor($exam);
        $scales = GradingScale::where('school_id', $exam->school_id)->where('education_stage', $exam->schoolClass->education_stage)->orderByDesc('minimum_percentage')->get();
        $marks = DB::table('exam_marks')->whereIn('exam_paper_id', $paperIds)->get()->keyBy(fn ($mark) => $mark->exam_paper_id.':'.$mark->student_id);
        $missingMarks = 0;
        $rows = collect();

        foreach ($students as $student) {
            $subjects = [];
            $weightedScore = 0.0;
            $weightedMaximum = 0.0;

            foreach ($approvedPapers as $paper) {
                $mark = $marks->get($paper->id.':'.$student->id);
                $score = $mark?->score;
                if ($score === null) {
                    $missingMarks++;
                }
                $numericScore = (float) ($score ?? 0);
                $percentage = (float) $paper->maximum_score > 0 ? round($numericScore / (float) $paper->maximum_score * 100, 2) : 0;
                $subjects[] = ['paper' => $paper, 'score' => $score, 'percentage' => $percentage, 'grade' => $this->gradeFor($percentage, $scales)];
                $weightedScore += $numericScore * (float) $paper->weighting;
                $weightedMaximum += (float) $paper->maximum_score * (float) $paper->weighting;
            }

            $average = $weightedMaximum > 0 ? round($weightedScore / $weightedMaximum * 100, 2) : 0;
            $rows->push(['student' => $student, 'subjects' => $subjects, 'total' => $weightedScore, 'average' => $average, 'grade' => $this->gradeFor($average, $scales)]);
        }

        $previousAverage = null;
        $previousPosition = 0;
        $results = $rows->sortByDesc('average')->values()->map(function (array $row, int $index) use (&$previousAverage, &$previousPosition) {
            $position = $previousAverage !== null && abs($row['average'] - $previousAverage) < 0.001 ? $previousPosition : $index + 1;
            $previousAverage = $row['average'];
            $previousPosition = $position;
            return $row + ['position' => $position];
        });

        $coverage = round($scales->sum(fn ($scale) => (float) $scale->maximum_percentage - (float) $scale->minimum_percentage + 0.01), 2);
        $allPapersApproved = $papers->isNotEmpty() && $approvedPapers->count() === $papers->count();
        $gradingReady = $scales->isNotEmpty() && $coverage >= 100.01;
        $hasStudents = $students->isNotEmpty();

        return ['papers' => $approvedPapers, 'results' => $results, 'readiness' => [
            'all_papers_approved' => $allPapersApproved,
            'grading_ready' => $gradingReady,
            'has_students' => $hasStudents,
            'missing_marks' => $missingMarks,
            'ready' => $allPapersApproved && $gradingReady && $hasStudents && $missingMarks === 0,
        ]];
    }

    protected function gradeFor(float $percentage, Collection $scales): string
    {
        return $scales->first(fn ($scale) => $percentage >= (float) $scale->minimum_percentage && $percentage <= (float) $scale->maximum_percentage)?->grade ?? '—';
    }

    protected function studentsFor(Exam $exam): Collection
    {
        return Student::where('school_id', $exam->school_id)
            ->where('school_class_id', $exam->school_class_id)
            ->when($exam->stream_id, fn (Builder $query, int $streamId) => $query->where('stream_id', $streamId))
            ->where('status', 'active')->orderBy('name')->get();
    }

    protected function selectedExamOrFail(): Exam
    {
        return Exam::with(['schoolClass', 'stream', 'papers.subject', 'term'])
            ->where('school_id', Auth::user()->school_id)->findOrFail($this->examId);
    }

    protected function canManage(): bool
    {
        return Auth::user()->hasPermission('exams.results');
    }

    public function render()
    {
        $school = Auth::user()->school;
        $term = $school->currentTerm();
        $exams = Exam::with(['schoolClass', 'stream'])->where('school_id', $school->id)->when($term, fn (Builder $query) => $query->where('term_id', $term->id))->orderByDesc('created_at')->get();
        $exam = $this->examId !== '' ? $this->selectedExamOrFail() : null;
        $report = $exam ? $this->calculate($exam) : ['papers' => collect(), 'results' => collect(), 'readiness' => null];

        return view('livewire.exam-results', compact('term', 'exams', 'exam') + $report + ['canManage' => $this->canManage(), 'pageTitle' => 'Exam Results']);
    }
}
