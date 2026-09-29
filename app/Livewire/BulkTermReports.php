<?php

namespace App\Livewire;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Services\StageTermReportCalculator;
use App\Support\TeacherAcademicScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class BulkTermReports extends Component
{
    public string $termId = '';

    public string $classId = '';

    public string $reportType = 'term';

    public function mount(): void
    {
        $user = Auth::user();
        $school = $user->school;
        $this->termId = (string) $school->currentTerm()?->id;
        $classes = SchoolClass::where('school_id', $school->id);
        if (TeacherAcademicScope::isTeacher($user)) {
            // Bulk reports expose every subject, so only the teacher's own class is eligible.
            $classes->whereIn('id', TeacherAcademicScope::classIds($user));
        }
        $this->classId = (string) $classes->orderBy('sort_order')->value('id');
    }

    public function showTermReports(): void
    {
        $this->reportType = 'term';
    }

    public function showEndOfYearReports(): void
    {
        $term = Term::where('school_id', Auth::user()->school_id)->where('term_number', 3)
            ->orderByDesc('year')->orderByDesc('id')->first();
        if (! $term) {
            $this->addError('termId', 'Create a third term before generating end-of-year reports.');

            return;
        }
        $this->reportType = 'end_of_year';
        $this->termId = (string) $term->id;
        $this->resetValidation('termId');
    }

    public function updatedTermId(): void
    {
        if ($this->reportType === 'end_of_year') {
            $term = Term::where('school_id', Auth::user()->school_id)->find($this->termId);
            if (! $term || (int) $term->term_number !== 3) {
                $this->reportType = 'term';
            }
        }
    }

    public function render()
    {
        $user = Auth::user();
        $school = $user->school;
        $term = Term::where('school_id', $school->id)->find($this->termId);
        $classQuery = SchoolClass::where('school_id', $school->id);
        if (TeacherAcademicScope::isTeacher($user)) {
            $classQuery->whereIn('id', TeacherAcademicScope::classIds($user));
        }
        $classes = $classQuery->orderBy('sort_order')->orderBy('name')->get();
        $classId = $classes->contains('id', (int) $this->classId) ? (int) $this->classId : null;

        $students = $term && $classId
            ? Student::with(['schoolClass', 'guardians'])
                ->where('school_id', $school->id)
                ->where(function (Builder $query) use ($classId): void {
                    $query->whereHas('enrolments', fn (Builder $enrolment) => $enrolment
                        ->where('term_id', $this->termId)->where('school_class_id', $classId))
                        ->orWhere(fn (Builder $fallback) => $fallback
                            ->whereDoesntHave('enrolments', fn (Builder $enrolment) => $enrolment->where('term_id', $this->termId))
                            ->where('school_class_id', $classId));
                })->orderBy('name')->get()
            : collect();

        $reports = $term ? $students->map(fn ($student) => [
            'student' => $student,
            'data' => StageTermReportCalculator::student($student, $term),
        ]) : collect();
        $ranked = $reports->filter(fn ($report) => $report['data']['marks']->isNotEmpty())->sortByDesc('data.average')->values();
        $positions = [];
        $lastAverage = null;
        $position = 0;
        foreach ($ranked as $index => $report) {
            $average = $report['data']['average'];
            if ($lastAverage === null || abs($average - $lastAverage) >= 0.001) {
                $position = $index + 1;
            }
            $positions[$report['student']->id] = $position;
            $lastAverage = $average;
        }

        return view('livewire.bulk-term-reports-v2', [
            'terms' => Term::where('school_id', $school->id)->latest('year')->get(),
            'classes' => $classes,
            'term' => $term,
            'reports' => $reports,
            'positions' => $positions,
            'school' => $school,
            'isEndOfYear' => $this->reportType === 'end_of_year' && (int) $term?->term_number === 3,
            'pageTitle' => 'Bulk Term Reports',
        ]);
    }
}
