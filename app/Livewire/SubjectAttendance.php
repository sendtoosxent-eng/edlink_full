<?php

namespace App\Livewire;

use App\Models\AttendanceRecord;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\StudentSubjectSelectionService;
use App\Support\TeacherAcademicScope;
use Illuminate\Database\Eloquent\Builder;
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

    public function mount(): void
    {
        abort_unless(Auth::user()->hasPermission('attendance.subject'), 403);
    }

    public function updatedSlotId(): void
    {
        $this->resetValidation();
        $this->statuses = [];
        $slot = $this->selectedSlot();

        if ($slot) {
            $saved = AttendanceRecord::where('school_id', Auth::user()->school_id)
                ->whereDate('attendance_date', today())
                ->where('session_key', $this->sessionKey($slot))
                ->pluck('status', 'student_id')
                ->all();
            foreach ($this->studentsFor($slot) as $student) {
                $this->statuses[$student->id] = $saved[$student->id] ?? 'present';
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
        }
        if ($rules) {
            $this->validate($rules, ['statuses.*.required' => 'Choose an attendance status for every learner.']);
        }

        DB::transaction(function () use ($school, $term, $slot, $students): void {
            foreach ($students as $student) {
                AttendanceRecord::updateOrCreate(
                    ['student_id' => $student->id, 'attendance_date' => today()->toDateString(), 'session_key' => $this->sessionKey($slot)],
                    [
                        'school_id' => $school->id,
                        'term_id' => $term->id,
                        'subject_id' => $slot->subject_id,
                        'school_class_id' => $slot->school_class_id,
                        'stream_id' => $slot->stream_id,
                        'lesson_time' => $slot->starts_at ?: null,
                        'status' => $this->statuses[$student->id],
                        'recorded_by' => Auth::id(),
                    ],
                );
            }
        });

        session()->flash('status', 'Subject attendance saved for '.$students->count().' learners.');
    }

    protected function availableSlots(): Collection
    {
        $user = Auth::user();
        $term = $user->school->currentTerm();
        if (! $term) {
            return collect();
        }
        $allowedAssignments = TeacherAcademicScope::subjectAssignments($user, $term->id);

        $timetableSlots = DB::table('timetable_slots')
            ->join('subjects', 'subjects.id', '=', 'timetable_slots.subject_id')
            ->join('school_classes', 'school_classes.id', '=', 'timetable_slots.school_class_id')
            ->leftJoin('streams', 'streams.id', '=', 'timetable_slots.stream_id')
            ->where('timetable_slots.school_id', $user->school_id)
            ->where('timetable_slots.term_id', $term->id)
            ->where('timetable_slots.user_id', $user->id)
            ->where('timetable_slots.day_of_week', today()->format('l'))
            ->whereNotNull('timetable_slots.subject_id')
            ->orderBy('timetable_slots.starts_at')
            ->get(['timetable_slots.*', 'subjects.name as subject_name', 'school_classes.name as class_name', 'streams.name as stream_name'])
            ->filter(fn (object $slot): bool => $allowedAssignments->contains(
                fn ($assignment) => (int) $assignment->school_class_id === (int) $slot->school_class_id
                    && (int) $assignment->subject_id === (int) $slot->subject_id
            ))
            ->map(function (object $slot): object {
                $slot->selection_key = 'slot:'.$slot->id;
                $slot->is_assignment_only = false;

                return $slot;
            });

        $scheduledPairs = $timetableSlots->mapWithKeys(
            fn (object $slot): array => [$slot->school_class_id.':'.$slot->subject_id => true],
        );

        $subjectNames = DB::table('subjects')->where('school_id', $user->school_id)->pluck('name', 'id');
        $classNames = DB::table('school_classes')->where('school_id', $user->school_id)->pluck('name', 'id');
        $assignmentFallbacks = $allowedAssignments
            ->reject(fn (object $assignment): bool => $scheduledPairs->has($assignment->school_class_id.':'.$assignment->subject_id))
            ->map(function (object $assignment) use ($term, $subjectNames, $classNames): object {
                $assignment->id = null;
                $assignment->term_id = $term->id;
                $assignment->subject_name = $subjectNames[$assignment->subject_id] ?? null;
                $assignment->class_name = $classNames[$assignment->school_class_id] ?? null;
                $assignment->selection_key = 'assignment:'.$assignment->school_class_id.':'.$assignment->subject_id;
                $assignment->stream_id = null;
                $assignment->stream_name = null;
                $assignment->starts_at = null;
                $assignment->ends_at = null;
                $assignment->is_assignment_only = true;

                return $assignment;
            });

        return $timetableSlots->concat($assignmentFallbacks)->values();
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
        $class = SchoolClass::where('school_id', Auth::user()->school_id)->findOrFail($slot->school_class_id);
        $query = Student::where('school_id', Auth::user()->school_id)
            ->where('school_class_id', $slot->school_class_id)
            ->when($slot->stream_id, fn (Builder $query, int $streamId) => $query->where('stream_id', $streamId))
            ->where('status', 'active')->orderBy('name');

        return StudentSubjectSelectionService::constrainStudentsForSubject(
            $query,
            $class,
            (int) $slot->term_id,
            (int) $slot->subject_id,
        )->get();
    }

    protected function sessionKey(object $slot): string
    {
        return $slot->is_assignment_only
            ? 'subject:'.$slot->subject_id.':class:'.$slot->school_class_id.':assignment'
            : 'subject:'.$slot->subject_id.':slot:'.$slot->id;
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
