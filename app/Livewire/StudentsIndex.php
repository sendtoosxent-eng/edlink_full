<?php

namespace App\Livewire;

use App\Models\Student;
use App\Models\StudentEnrolment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class StudentsIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'active';

    public ?int $openStudentId = null;

    public function mount(Request $request): void
    {
        $studentId = $request->integer('student');
        if ($studentId && Student::where('school_id', Auth::user()->school_id)->whereKey($studentId)->exists()) {
            $this->openStudentId = $studentId;
        }
    }

    public function toggleStatus(int $id): void
    {
        $student = Student::where('school_id', Auth::user()->school_id)->findOrFail($id);
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
        $students = Student::with(['schoolClass', 'stream', 'category'])
            ->where('school_id', Auth::user()->school_id)
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
        ]);
    }
}
