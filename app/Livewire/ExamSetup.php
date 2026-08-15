<?php

namespace App\Livewire;

use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ExamSetup extends Component
{
    public string $name = '';
    public string $classId = '';
    public string $streamId = '';
    public string $examId = '';
    public string $subjectId = '';
    public string $maximumScore = '100';
    public string $weighting = '1';

    public function mount(): void
    {
        $this->authorizeManagement();
    }

    public function addExam(): void
    {
        $this->authorizeManagement();
        $school = Auth::user()->school;
        $term = $school->currentTerm();
        if (! $term || ! $term->isOpen()) {
            session()->flash('error', 'Open a term before creating an exam.');

            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'classId' => ['required', Rule::exists('school_classes', 'id')->where('school_id', $school->id)],
            'streamId' => [
                'nullable',
                Rule::exists('streams', 'id')->where(fn ($query) => $query
                    ->where('school_id', $school->id)
                    ->where('school_class_id', $this->classId)),
            ],
        ]);

        $exam = Exam::create([
            'school_id' => $school->id,
            'term_id' => $term->id,
            'school_class_id' => $validated['classId'],
            'stream_id' => $validated['streamId'] ?: null,
            'name' => $validated['name'],
            'status' => 'draft',
        ]);
        $this->examId = (string) $exam->id;
        $this->reset(['name', 'streamId']);
        session()->flash('status', 'Exam created. Add its subject papers below.');
    }

    public function addPaper(): void
    {
        $this->authorizeManagement();
        $schoolId = Auth::user()->school_id;
        $exam = Exam::where('school_id', $schoolId)->findOrFail($this->examId);
        $validated = $this->validate([
            'subjectId' => ['required', Rule::exists('subjects', 'id')->where('school_id', $schoolId)],
            'maximumScore' => ['required', 'numeric', 'min:1', 'max:10000'],
            'weighting' => ['required', 'numeric', 'min:0.01', 'max:100'],
        ]);
        ExamPaper::updateOrCreate(
            ['exam_id' => $exam->id, 'subject_id' => $validated['subjectId']],
            ['maximum_score' => $validated['maximumScore'], 'weighting' => $validated['weighting']],
        );
        session()->flash('status', 'Exam paper saved.');
    }

    private function authorizeManagement(): void
    {
        abort_unless(Auth::user()->hasPermission('exams.setup'), 403);
    }

    public function render()
    {
        $school = Auth::user()->school;

        return view('livewire.exam-setup', [
            'term' => $school->currentTerm(),
            'classes' => SchoolClass::where('school_id', $school->id)->get(),
            'streams' => $this->classId
                ? Stream::where('school_id', $school->id)->where('school_class_id', $this->classId)->get()
                : collect(),
            'subjects' => Subject::where('school_id', $school->id)->get(),
            'exams' => Exam::with(['schoolClass', 'papers.subject'])->where('school_id', $school->id)->latest()->get(),
            'pageTitle' => 'Exam Setup',
        ]);
    }
}
