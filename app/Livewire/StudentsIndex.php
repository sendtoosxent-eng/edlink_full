<?php

namespace App\Livewire;

use App\Models\Student;
use App\Models\StudentEnrolment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use App\Support\TeacherAcademicScope;

#[Layout('layouts.app')]
class StudentsIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'active';

    public ?int $openStudentId = null;

    public function mount(Request $request): void
    {
        abort_unless(TeacherAcademicScope::canViewStudentDirectory(Auth::user()), 403);
        $studentId = $request->integer('student');
        if ($studentId && $this->studentQuery()->whereKey($studentId)->exists()) {
            $this->openStudentId = $studentId;
        }
    }

    public function toggleStatus(int $id): void
    {
        abort_unless(Auth::user()->hasPermission('students.manage'), 403);
        $student = $this->studentQuery()->findOrFail($id);
        if ($student->status === 'graduated') {
            session()->flash('status', 'Graduates can only be restored from the Graduates & Alumni register with an audited reason.');
            return;
        }
        $student->status = $student->status === 'active' ? 'inactive' : 'active';
        $student->save();

        $term = Auth::user()->school->currentTerm();
        if ($term) {
            StudentEnrolment::where('student_id', $student->id)
                ->where('term_id', $term->id)
                ->update(['status' => $student->status]);
        }

        session()->flash('status', $student->name.' marked as '.$student->status.'.');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $students = $this->studentQuery()->with(['schoolClass', 'stream', 'category'])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                  ->orWhere('admission_no', 'like', '%'.$this->search.'%');
            }))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.students-index', [
            'students' => $students,
            'pageTitle' => 'All Students',
            'canManageStudents' => Auth::user()->hasPermission('students.manage'),
        ]);
    }

    private function studentQuery()
    {
        $query = Student::query()->where('school_id', Auth::user()->school_id);
        if (! TeacherAcademicScope::canViewAllStudents(Auth::user())) {
            $query->whereIn('school_class_id', TeacherAcademicScope::classIds(Auth::user()));
        }
        return $query;
    }
}
