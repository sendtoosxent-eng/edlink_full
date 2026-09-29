<?php

namespace App\Livewire;

use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\ExamCategoryGroup;
use App\Models\ExamCategoryGroupAssignment;
use App\Models\ExamPaper;
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

    public function updatedClassId(): void
    {
        $this->reset(['streamId', 'categoryId']);
        $this->resetValidation(['streamId', 'categoryId']);
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

        $group = $this->groupForClass($school->id, $term->id, (int) $this->classId);
        $categories = $group?->categories ?? collect();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('exams', 'name')->where(fn ($query) => $query
                ->where('school_id', $school->id)->where('term_id', $term->id)->where('school_class_id', $this->classId)
                ->when($this->streamId !== '', fn ($stream) => $stream->where('stream_id', $this->streamId), fn ($stream) => $stream->whereNull('stream_id')))],
            'classId' => ['required', Rule::exists('school_classes', 'id')->where('school_id', $school->id)],
            'streamId' => [
                'nullable',
                Rule::exists('streams', 'id')->where(fn ($query) => $query
                    ->where('school_id', $school->id)
                    ->where('school_class_id', $this->classId)),
            ],
            'categoryId' => [
                $group && $categories->count() > 1 ? 'required' : 'nullable',
                Rule::in($categories->pluck('id')->map(fn ($id) => (string) $id)->all()),
            ],
        ]);
        $categoryId = $validated['categoryId'] ?: ($group && $categories->count() === 1 ? $categories->first()->id : null);

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
        if (! $exam->term->isOpen() || $exam->isPublished()) {
            $this->addError('examId', 'Unpublish the exam and ensure its term is open before changing its papers.');

            return;
        }
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
        $exam = Exam::with('term')->where('school_id', $schoolId)->findOrFail($examId);
        if (! $exam->term->isOpen() || $exam->isPublished()) {
            $this->addError('examId', 'Unpublish the exam and ensure its term is open before changing its category.');

            return;
        }
        $group = $this->groupForClass($schoolId, $exam->term_id, $exam->school_class_id);
        $exam->update([
            'exam_category_id' => $categoryId === ''
                ? null
                : ExamCategory::where('school_id', $schoolId)->where('term_id', $exam->term_id)
                    ->where('exam_category_group_id', $group?->id)->findOrFail($categoryId)->id,
        ]);
        session()->flash('status', 'Exam category updated.');
    }

    private function authorizeManagement(): void
    {
        abort_unless(Auth::user()->hasPermission('exams.setup'), 403);
    }

    private function groupForClass(int $schoolId, int $termId, int $classId): ?ExamCategoryGroup
    {
        $groupId = ExamCategoryGroupAssignment::where([
            'school_id' => $schoolId, 'term_id' => $termId, 'school_class_id' => $classId,
        ])->value('exam_category_group_id');

        return $groupId ? ExamCategoryGroup::with('categories')->where('school_id', $schoolId)->where('term_id', $termId)->find($groupId) : null;
    }

    public function render()
    {
        $school = Auth::user()->school;
        $currentTerm = $school->currentTerm();
        $categoryGroup = $currentTerm && $this->classId !== ''
            ? $this->groupForClass($school->id, $currentTerm->id, (int) $this->classId)
            : null;
        $categories = $categoryGroup?->categories ?? collect();
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

        $exams = Exam::with(['schoolClass', 'stream', 'term', 'category.group', 'papers.subject'])->where('school_id', $school->id)->latest()->get();
        $categoryOptions = $exams->mapWithKeys(function (Exam $exam) use ($school) {
            $group = $this->groupForClass($school->id, $exam->term_id, $exam->school_class_id);

            return [$exam->id => $group?->categories ?? collect()];
        });

        return view('livewire.exam-setup', [
            'term' => $currentTerm,
            'classes' => SchoolClass::where('school_id', $school->id)->get(),
            'categoryGroup' => $categoryGroup,
            'categories' => $categories,
            'streams' => $this->classId
                ? Stream::where('school_id', $school->id)->where('school_class_id', $this->classId)->get()
                : collect(),
            'subjects' => $subjects,
            'exams' => $exams,
            'categoryOptions' => $categoryOptions,
            'pageTitle' => 'Exam Setup',
        ]);
    }
}
