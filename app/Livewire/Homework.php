<?php

namespace App\Livewire;

use App\Models\HomeworkAssignment;
use App\Models\HomeworkSubmission;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Services\StudentSubjectSelectionService;
use App\Support\TeacherAcademicScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Homework extends Component
{
    use WithFileUploads;

    public string $classId = '';

    public string $streamId = '';

    public string $subjectId = '';

    public string $title = '';

    public string $instructions = '';

    public string $dueAt = '';

    public string $maximumScore = '100';

    public $attachment;

    public ?int $selectedAssignmentId = null;

    public string $answer = '';

    public array $reviewScores = [];

    public array $reviewFeedback = [];

    public $submissionAttachment;

    public function mount(): void
    {
        $this->dueAt = now()->addDays(2)->format('Y-m-d\TH:i');
    }

    private function isStudent(): bool
    {
        return Auth::user()->role === 'student';
    }

    private function linkedStudent(): ?Student
    {
        return Auth::user()->portalStudents()->where('students.school_id', Auth::user()->school_id)->where('students.status', 'active')->first();
    }

    private function offerings()
    {
        $user = Auth::user();
        $term = $user->school->currentTerm();
        if (TeacherAcademicScope::isTeacher($user)) {
            return TeacherAcademicScope::subjectAssignments($user, $term?->id);
        }
        $query = DB::table('staff_subjects')->where('school_id', $user->school_id)->when($term, fn ($q) => $q->where('term_id', $term->id));
        if (! in_array($user->role, ['admin', 'superadmin', 'academic_admin'], true)) {
            $query->where('user_id', $user->id);
        }

        return $query->get(['school_class_id', 'subject_id'])->filter(fn ($row) => $row->school_class_id)->unique(fn ($row) => $row->school_class_id.'-'.$row->subject_id)->values();
    }

    public function createAssignment(): void
    {
        $user = Auth::user();
        abort_if($this->isStudent() || $user->role === 'parent', 403);
        $term = $user->school->currentTerm();
        if (! $term) {
            throw ValidationException::withMessages(['classId' => 'Open a current term before assigning homework.']);
        }
        $data = $this->validate([
            'classId' => ['required', Rule::exists('school_classes', 'id')->where('school_id', $user->school_id)],
            'streamId' => ['nullable', Rule::exists('streams', 'id')->where(fn ($query) => $query->where('school_id', $user->school_id)->where('school_class_id', $this->classId))],
            'subjectId' => ['required', Rule::exists('subjects', 'id')->where('school_id', $user->school_id)],
            'title' => 'required|string|max:255', 'instructions' => 'required|string|max:10000',
            'dueAt' => 'required|date|after:now', 'maximumScore' => 'required|integer|min:1|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,webp,zip|max:10240',
        ]);
        abort_unless($this->offerings()->contains(fn ($row) => (int) $row->school_class_id === (int) $data['classId'] && (int) $row->subject_id === (int) $data['subjectId']), 403);
        abort_unless(SchoolClass::where('school_id', $user->school_id)->whereKey($data['classId'])->exists() && Subject::where('school_id', $user->school_id)->whereKey($data['subjectId'])->exists(), 404);
        $path = $this->attachment?->store('homework/assignments', 'local');
        HomeworkAssignment::create([
            'school_id' => $user->school_id, 'term_id' => $term->id, 'teacher_id' => $user->id,
            'school_class_id' => $data['classId'], 'stream_id' => $data['streamId'] ?: null, 'subject_id' => $data['subjectId'],
            'title' => $data['title'], 'instructions' => $data['instructions'], 'due_at' => $data['dueAt'],
            'maximum_score' => $data['maximumScore'], 'attachment_path' => $path,
            'attachment_name' => $this->attachment?->getClientOriginalName(), 'published_at' => now(),
        ]);
        $this->reset(['classId', 'streamId', 'subjectId', 'title', 'instructions', 'attachment']);
        $this->maximumScore = '100';
        $this->dueAt = now()->addDays(2)->format('Y-m-d\TH:i');
        session()->flash('status', 'Homework published to the class.');
    }

    public function selectAssignment(int $id): void
    {
        $assignment = $this->visibleAssignments()->firstWhere('id', $id);
        abort_unless($assignment, 404);
        $this->selectedAssignmentId = $id;
        $submission = $this->isStudent() ? $assignment->submissions->firstWhere('student_id', $this->linkedStudent()?->id) : null;
        $this->answer = (string) ($submission?->answer ?? '');
        $this->reviewScores = $assignment->submissions->mapWithKeys(fn ($item) => [$item->id => (string) ($item->score ?? '')])->all();
        $this->reviewFeedback = $assignment->submissions->mapWithKeys(fn ($item) => [$item->id => (string) ($item->feedback ?? '')])->all();
    }

    public function submitHomework(): void
    {
        abort_unless($this->isStudent(), 403);
        $student = $this->linkedStudent();
        abort_unless($student, 403);
        $assignment = $this->visibleAssignments()->firstWhere('id', $this->selectedAssignmentId);
        abort_unless($assignment, 404);
        $this->validate(['answer' => 'nullable|string|max:20000', 'submissionAttachment' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,webp,zip|max:10240']);
        if (! filled($this->answer) && ! $this->submissionAttachment) {
            throw ValidationException::withMessages(['answer' => 'Write an answer or attach your work.']);
        }
        $existing = HomeworkSubmission::where(['homework_assignment_id' => $assignment->id, 'student_id' => $student->id])->first();
        $oldPath = $existing?->attachment_path;
        $path = $this->submissionAttachment?->store('homework/submissions', 'local') ?? $oldPath;
        $existing?->update(['answer' => $this->answer ?: null, 'attachment_path' => $path, 'attachment_name' => $this->submissionAttachment?->getClientOriginalName() ?? $existing->attachment_name, 'submitted_by' => Auth::id(), 'submitted_at' => now(), 'status' => $assignment->due_at->isPast() ? 'late' : 'submitted', 'score' => null, 'feedback' => null, 'reviewed_at' => null])
            ?? HomeworkSubmission::create(['homework_assignment_id' => $assignment->id, 'student_id' => $student->id, 'submitted_by' => Auth::id(), 'answer' => $this->answer ?: null, 'attachment_path' => $path, 'attachment_name' => $this->submissionAttachment?->getClientOriginalName(), 'submitted_at' => now(), 'status' => $assignment->due_at->isPast() ? 'late' : 'submitted']);
        if ($this->submissionAttachment && $oldPath && $oldPath !== $path) {
            Storage::disk('local')->delete($oldPath);
        }
        $this->reset('submissionAttachment');
        session()->flash('status', 'Your homework was submitted.');
    }

    public function review(int $submissionId): void
    {
        $submission = HomeworkSubmission::with('assignment')->findOrFail($submissionId);
        abort_unless($submission->assignment->school_id === Auth::user()->school_id && ($submission->assignment->teacher_id === Auth::id() || in_array(Auth::user()->role, ['admin', 'superadmin', 'academic_admin'], true)), 403);
        $this->validate(["reviewScores.$submissionId" => 'required|numeric|min:0|max:'.$submission->assignment->maximum_score, "reviewFeedback.$submissionId" => 'nullable|string|max:5000']);
        $submission->update(['score' => $this->reviewScores[$submissionId], 'feedback' => $this->reviewFeedback[$submissionId] ?: null, 'status' => 'reviewed', 'reviewed_at' => now()]);
        session()->flash('status', 'Submission reviewed.');
    }

    private function visibleAssignments()
    {
        $user = Auth::user();
        $term = $user->school->currentTerm();
        $query = HomeworkAssignment::with(['subject', 'schoolClass', 'stream', 'teacher', 'submissions.student'])->where('school_id', $user->school_id)->when($term, fn ($q) => $q->where('term_id', $term->id));
        if ($this->isStudent()) {
            $student = $this->linkedStudent();

            return $student
                ? $query->whereNotNull('published_at')
                    ->where('school_class_id', $student->school_class_id)
                    ->where(fn ($q) => $q->whereNull('stream_id')->orWhere('stream_id', $student->stream_id))
                    ->latest('due_at')
                    ->get()
                    ->filter(fn ($assignment) => StudentSubjectSelectionService::studentTakesSubject($student, $assignment->term_id, $assignment->subject_id))
                    ->values()
                : collect();
        }
        if ($user->role === 'parent') {
            return collect();
        }
        if (! in_array($user->role, ['admin', 'superadmin', 'academic_admin'], true)) {
            $query->where('teacher_id', $user->id);
        }

        return $query->latest()->get();
    }

    public function render()
    {
        $offerings = $this->isStudent() || Auth::user()->role === 'parent' ? collect() : $this->offerings();
        $classes = SchoolClass::where('school_id', Auth::user()->school_id)->whereIn('id', $offerings->pluck('school_class_id'))->orderBy('name')->get();
        $subjects = Subject::where('school_id', Auth::user()->school_id)->whereIn('id', $offerings->pluck('subject_id'))->orderBy('name')->get();
        $assignments = $this->visibleAssignments();

        return view('livewire.homework', compact('assignments', 'classes', 'subjects', 'offerings') + ['isStudent' => $this->isStudent(), 'selectedAssignment' => $assignments->firstWhere('id',$this->selectedAssignmentId), 'student' => $this->linkedStudent(), 'pageTitle' => 'Homework']);
    }
}
