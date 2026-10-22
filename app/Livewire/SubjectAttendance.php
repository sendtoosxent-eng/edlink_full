<?php

namespace App\Livewire;

use App\Models\AttendanceRecord;
use App\Services\StudentActivityNotifier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SubjectAttendance extends Component
{
    public string $slotId = '';

    public array $statuses = [];

    public array $notes = [];

    public bool $showStudentImages = false;

    public function mount(): void
    {
        abort_unless(Auth::user()->hasPermission('attendance.subject'), 403);
    }

    public function updatedSlotId(): void
    {
        $this->resetValidation();
        $this->statuses = [];
        $this->notes = [];
        $slot = $this->selectedSlot();

        if ($slot) {
            $saved = AttendanceRecord::where('school_id', Auth::user()->school_id)
                ->whereDate('attendance_date', today())
                ->where('session_key', $this->sessionKey($slot))
                ->get(['student_id', 'status', 'note'])->keyBy('student_id');
            foreach ($this->studentsFor($slot) as $student) {
                $savedRecord = $saved[$student->id] ?? null;
                $this->statuses[$student->id] = $savedRecord?->status ?? 'present';
                $this->notes[$student->id] = $savedRecord?->status === 'late'
                    ? preg_replace('/^Late by\s+/i', '', (string) $savedRecord->note, 1)
                    : ($savedRecord?->note ?? '');
            }
        }
    }

    public function markAll(string $status): void
    {
        abort_unless(in_array($status, AttendanceRecord::STATUSES, true), 422);
        $slot = $this->selectedSlotOrFail();
        foreach ($this->studentsFor($slot) as $student) {
            $this->statuses[$student->id] = $status;
        }
    }

    public function save(): void
    {
        abort_unless(Auth::user()->hasPermission('attendance.subject'), 403);
        $school = Auth::user()->school;
        $term = $school->currentTerm();
        $slot = $this->selectedSlotOrFail();

        if (! $term?->isOpen()) {
            $this->addError('slotId', 'Subject attendance can only be recorded in an open term.');

            return;
        }

        $students = $this->studentsFor($slot);
        $rules = [];
        foreach ($students as $student) {
            $rules['statuses.'.$student->id] = ['required', 'in:'.implode(',', AttendanceRecord::STATUSES)];
            $status = $this->statuses[$student->id] ?? 'present';
            $rules['notes.'.$student->id] = $status === 'present'
                ? ['nullable', 'string', 'max:500']
                : ($status === 'late' ? ['required', 'integer', 'min:1', 'max:1440'] : ['required', 'string', 'max:500']);
        }
        if ($rules) {
            $this->validate($rules, ['statuses.*.required' => 'Choose an attendance status for every learner.', 'notes.*.required' => 'Add a reason or the number of minutes late before saving.']);
        }

        DB::transaction(function () use ($school, $term, $slot, $students): void {
            foreach ($students as $student) {
                $record = AttendanceRecord::updateOrCreate(
                    ['student_id' => $student->id, 'attendance_date' => today()->toDateString(), 'session_key' => $this->sessionKey($slot)],
                    [
                        'school_id' => $school->id,
                        'term_id' => $term->id,
                        'subject_id' => $slot->subject_id,
                        'school_class_id' => $slot->school_class_id,
                        'stream_id' => $slot->stream_id,
                        'lesson_time' => $slot->starts_at ?: null,
                        'status' => $this->statuses[$student->id],
                        'note' => $this->statuses[$student->id] === 'late' ? 'Late by '.($this->notes[$student->id] ?? '').' minutes' : ($this->notes[$student->id] ?? null),
                        'recorded_by' => Auth::id(),
                    ],
                );
                app(StudentActivityNotifier::class)->attendance($record);
            }
        });

        session()->flash('status', 'Subject attendance saved for '.$students->count().' learners.');
    }

    protected function availableSlots(): Collection
    {
        return \App\Support\SubjectAttendanceRegisters::forUser(Auth::user());
    }

    protected function selectedSlot(): ?object
    {
        if ($this->slotId === '') {
            return null;
        }

        return $this->availableSlots()->firstWhere('selection_key', $this->slotId);
    }

    protected function selectedSlotOrFail(): object
    {
        return $this->selectedSlot() ?? abort(403);
    }

    protected function studentsFor(object $slot): Collection
    {
        return \App\Support\SubjectAttendanceRegisters::studentsFor(Auth::user(), $slot);
    }

    protected function sessionKey(object $slot): string
    {
        return \App\Support\SubjectAttendanceRegisters::sessionKey($slot);
    }

    public function render()
    {
        $slots = $this->availableSlots();
        $slot = $this->selectedSlot();
        $students = $slot ? $this->studentsFor($slot) : collect();
        $saved = $slot ? AttendanceRecord::where('school_id', Auth::user()->school_id)->whereDate('attendance_date', today())->where('session_key', $this->sessionKey($slot))->count() : 0;

        return view('livewire.subject-attendance', [
            'term' => Auth::user()->school->currentTerm(),
            'lessons' => $slots,
            'lesson' => $slot,
            'students' => $students,
            'saved' => $saved,
            'pageTitle' => 'Subject Attendance',
        ]);
    }
}
