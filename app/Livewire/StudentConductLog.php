<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Student;
use App\Models\StudentConduct;
use App\Models\SchoolSetting;
use App\Services\StudentActivityNotifier;
use App\Support\StudentConductAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

class StudentConductLog extends Component
{
    #[Locked]
    public int $studentId;

    #[Locked]
    public ?int $editingId = null;

    public string $occurredOn = '';
    public string $kind = 'good';
    public string $description = '';
    public string $action = '';

    public function mount(Student $student): void
    {
        $this->studentId = $student->id;
        $this->student();
        $this->occurredOn = $this->todayDate();
    }

    private function todayDate(): string
    {
        $timezone = (string) SchoolSetting::getValue(Auth::user()->school_id, 'timezone', 'Africa/Kampala');
        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            $timezone = config('app.timezone');
        }
        return now($timezone)->toDateString();
    }

    private function student(): Student
    {
        $student = Student::with('schoolClass')->findOrFail($this->studentId);
        abort_unless(StudentConductAccess::canView(Auth::user(), $student), 403);
        return $student;
    }

    public function edit(int $id): void
    {
        $student = $this->student();
        abort_unless(StudentConductAccess::canManage(Auth::user(), $student), 403);
        $record = StudentConduct::where('school_id', $student->school_id)->where('student_id', $student->id)->findOrFail($id);
        $this->resetValidation();
        $this->editingId = $record->id;
        $this->occurredOn = $record->occurred_on->toDateString();
        $this->kind = $record->kind;
        $this->description = $record->description;
        $this->action = $record->action ?? '';
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'kind', 'description', 'action']);
        $this->occurredOn = $this->todayDate();
        $this->resetValidation();
    }

    public function save(): void
    {
        $student = $this->student();
        abort_unless(StudentConductAccess::canManage(Auth::user(), $student), 403);
        $this->description = trim($this->description);
        $this->action = trim($this->action);
        $this->validate([
            'occurredOn' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$this->todayDate()],
            'kind' => ['required', 'in:good,bad'],
            'description' => ['required', 'string', 'max:5000'],
            'action' => ['nullable', 'string', 'max:2000'],
        ], ['occurredOn.before_or_equal' => 'The conduct date cannot be later than today.']);

        DB::transaction(function () use ($student): void {
            $record = $this->editingId
                ? StudentConduct::where('school_id', $student->school_id)->where('student_id', $student->id)->lockForUpdate()->findOrFail($this->editingId)
                : new StudentConduct(['school_id' => $student->school_id, 'student_id' => $student->id, 'recorded_by' => Auth::id()]);
            $isUpdate = $record->exists;
            $record->fill(['occurred_on' => $this->occurredOn, 'kind' => $this->kind, 'description' => $this->description, 'action' => $this->action ?: null, 'updated_by' => Auth::id()]);
            $record->save();
            AuditLog::record($student->school_id, $isUpdate ? 'student.conduct.updated' : 'student.conduct.recorded', $record,
                ['student_id' => $student->id, 'kind' => $record->kind, 'occurred_on' => $record->occurred_on->toDateString()]);
            app(StudentActivityNotifier::class)->conduct($record, $isUpdate);
        });
        $this->cancelEdit();
        session()->flash('conductStatus', 'Conduct saved. Linked parents have been notified in their accounts.');
    }

    public function render()
    {
        $student = $this->student();
        return view('livewire.student-conduct-log', [
            'maxConductDate' => $this->todayDate(),
            'canManageConduct' => StudentConductAccess::canManage(Auth::user(), $student),
            'records' => StudentConduct::with(['recordedBy', 'updatedBy'])->where('school_id', $student->school_id)->where('student_id', $student->id)->latest('occurred_on')->latest('id')->get(),
        ]);
    }
}
