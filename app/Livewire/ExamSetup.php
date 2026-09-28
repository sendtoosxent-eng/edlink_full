<?php

namespace App\Livewire;

use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\ExamCategory;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ExamSetup extends Component
{
    public string $name = '';
    public string $classId = '';
    public string $streamId = '';
    public string $categoryId = '';
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
            'categoryId' => [
                ExamCategory::where('school_id', $school->id)->where('term_id', $term->id)->count() > 1 ? 'required' : 'nullable',
                Rule::exists('exam_categories', 'id')->where('school_id', $school->id)->where('term_id', $term->id),
            ],
        ]);
        $categories = ExamCategory::where('school_id', $school->id)->where('term_id', $term->id)->orderBy('sort_order')->get();
        $categoryId = $validated['categoryId'] ?: ($categories->count() === 1 ? $categories->first()->id : null);

        $exam = Exam::create([
            'school_id' => $school->id,
            'term_id' => $term->id,
            'exam_category_id' => $categoryId,
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
        $offeredSubjectIds = DB::table('class_subjects')
            ->where('school_id', $schoolId)
            ->where('term_id', $exam->term_id)
            ->where('school_class_id', $exam->school_class_id)
            ->pluck('subject_id');
        $validated = $this->validate([
            'subjectId' => ['required', Rule::exists('subjects', 'id')->where('school_id', $schoolId), Rule::in($offeredSubjectIds)],
            'maximumScore' => ['required', 'numeric', 'min:1', 'max:10000'],
            'weighting' => ['required', 'numeric', 'min:0.01', 'max:100'],
        ]);
        $paper = ExamPaper::firstOrNew(
            ['exam_id' => $exam->id, 'subject_id' => $validated['subjectId']],
        );
        if ($paper->exists && DB::table('exam_marks')->where('exam_paper_id', $paper->id)->exists()) {
            $this->addError('subjectId', 'This paper already has marks. Its scoring rules cannot be changed.');

            return;
        }
        $paper->fill(['maximum_score' => $validated['maximumScore'], 'weighting' => $validated['weighting']])->save();
        $this->reset(['subjectId']);
        session()->flash('status', 'Exam paper saved.');
    }

    public function assignCategory(int $examId, string $categoryId): void
    {
        $this->authorizeManagement();
        $schoolId = Auth::user()->school_id;
        $exam = Exam::where('school_id', $schoolId)->findOrFail($examId);
        $exam->update([
            'exam_category_id' => $categoryId === ''
                ? null
                : ExamCategory::where('school_id', $schoolId)->where('term_id', $exam->term_id)->findOrFail($categoryId)->id,
        ]);
        session()->flash('status', 'Exam category updated.');
    }

    private function authorizeManagement(): void
    {
        abort_unless(Auth::user()->hasPermission('exams.setup'), 403);
    }

    public function render()
    {
        $school = Auth::user()->school;
        $selectedExam = $this->examId !== ''
            ? Exam::where('school_id', $school->id)->find($this->examId)
            : null;
        $subjects = $selectedExam
            ? Subject::where('school_id', $school->id)
                ->whereIn('id', DB::table('class_subjects')
                    ->where('school_id', $school->id)
                    ->where('term_id', $selectedExam->term_id)
                    ->where('school_class_id', $selectedExam->school_class_id)
                    ->select('subject_id'))
                ->orderBy('name')->get()
            : collect();

        return view('livewire.exam-setup', [
            'term' => $school->currentTerm(),
            'classes' => SchoolClass::where('school_id', $school->id)->get(),
            'categories' => ExamCategory::where('school_id', $school->id)->where('term_id', $school->currentTerm()?->id)->orderBy('sort_order')->get(),
            'allCategories' => ExamCategory::where('school_id', $school->id)->whereNotNull('term_id')->orderBy('sort_order')->get(),
            'streams' => $this->classId
                ? Stream::where('school_id', $school->id)->where('school_class_id', $this->classId)->get()
                : collect(),
            'subjects' => $subjects,
            'exams' => Exam::with(['schoolClass', 'stream', 'term', 'category', 'papers.subject'])->where('school_id', $school->id)->latest()->get(),
            'pageTitle' => 'Exam Setup',
        ]);
    }
}
