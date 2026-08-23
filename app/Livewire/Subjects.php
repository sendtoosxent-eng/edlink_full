<?php

namespace App\Livewire;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use App\Support\TeacherAcademicScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Subjects extends Component
{
    public string $name = '';

    public string $code = '';

    public string $subjectId = '';

    public string $classId = '';

    public string $teacherId = '';

    private function authorizeSubjects(): void
    {
        abort_unless(Auth::user()?->hasPermission('academics.subjects'), 403);
    }

    private function canManage(): bool
    {
        return ! TeacherAcademicScope::isTeacher(Auth::user());
    }

    public function addSubject(): void
    {
        $this->authorizeSubjects();
        abort_unless($this->canManage(), 403);
        $this->validate(['name' => 'required|string|max:255', 'code' => 'nullable|string|max:30']);
        Subject::firstOrCreate(
            ['school_id' => Auth::user()->school_id, 'name' => $this->name],
            ['code' => $this->code ?: null],
        );
        $this->reset(['name', 'code']);
        session()->flash('status', 'Subject saved.');
    }

    public function assign(): void
    {
        $this->authorizeSubjects();
        abort_unless($this->canManage(), 403);
        $school = Auth::user()->school;
        $term = $school->currentTerm();
        if (! $term) {
            session()->flash('error', 'Open a term before assigning subjects.');

            return;
        }

        $schoolId = $school->id;
        $this->validate([
            'subjectId' => ['required', Rule::exists('subjects', 'id')->where('school_id', $schoolId)],
            'classId' => ['required', Rule::exists('school_classes', 'id')->where('school_id', $schoolId)],
            'teacherId' => ['required', Rule::exists('users', 'id')->where(fn ($query) => $query
                ->where('school_id', $schoolId)->where('employment_status', 'active')->where('role', 'teacher'))],
        ]);

        DB::transaction(function () use ($school, $term): void {
            DB::table('class_subjects')->updateOrInsert(
                ['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $this->classId, 'subject_id' => $this->subjectId],
                ['updated_at' => now(), 'created_at' => now()],
            );
            DB::table('staff_subjects')->updateOrInsert(
                ['school_id' => $school->id, 'term_id' => $term->id, 'user_id' => $this->teacherId, 'subject_id' => $this->subjectId, 'school_class_id' => $this->classId],
                ['updated_at' => now(), 'created_at' => now()],
            );
        });
        session()->flash('status', 'Subject assigned for the current term.');
    }

    public function render()
    {
        $this->authorizeSubjects();
        $user = Auth::user();
        $school = $user->school;
        $term = $school->currentTerm();
        $canManage = $this->canManage();
        $assignments = $canManage ? collect() : TeacherAcademicScope::subjectAssignments($user, $term?->id);

        return view('livewire.subjects', [
            'term' => $term,
            'subjects' => Subject::where('school_id', $school->id)
                ->when(! $canManage, fn ($query) => $query->whereIn('id', $assignments->pluck('subject_id')))
                ->orderBy('name')->get(),
            'classes' => SchoolClass::where('school_id', $school->id)
                ->when(! $canManage, fn ($query) => $query->whereIn('id', $assignments->pluck('school_class_id')))
                ->orderBy('name')->get(),
            'teachers' => $canManage
                ? User::where('school_id', $school->id)->where('employment_status', 'active')->where('role', 'teacher')->orderBy('name')->get()
                : collect(),
            'canManage' => $canManage,
            'pageTitle' => 'Subjects',
        ]);
    }
}
