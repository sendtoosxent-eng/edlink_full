<?php

namespace App\Support;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentSubjectSelectionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SubjectAttendanceRegisters
{
    public static function forUser(User $user, ?string $date = null): Collection
    {
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
            ->where('timetable_slots.day_of_week', Carbon::parse($date ?? today()->toDateString())->format('l'))
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

    public static function studentsFor(User $user, object $slot): Collection
    {
        $class = SchoolClass::where('school_id', $user->school_id)->findOrFail($slot->school_class_id);
        $query = Student::where('school_id', $user->school_id)
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

    public static function sessionKey(object $slot): string
    {
        return $slot->is_assignment_only
            ? 'subject:'.$slot->subject_id.':class:'.$slot->school_class_id.':assignment'
            : 'subject:'.$slot->subject_id.':slot:'.$slot->id;
    }
}
