<?php

namespace App\Support;

use App\Models\HomeworkAssignment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class MobileAccess
{
    public static function student(User $user, ?int $studentId = null): Student
    {
        abort_unless(in_array($user->role, ['student', 'parent'], true), 403);

        $query = $user->portalStudents()
            ->where('students.school_id', $user->school_id)
            ->where('students.status', 'active');

        if ($studentId) {
            $query->where('students.id', $studentId);
        }

        return $query->firstOrFail();
    }

    public static function teacherStudentQuery(User $user, int $classId, ?int $subjectId = null): Builder
    {
        abort_unless(TeacherAcademicScope::isTeacher($user), 403);
        $termId = $user->school?->currentTerm()?->id;
        $allowed = TeacherAcademicScope::classIds($user)->contains($classId)
            || ($subjectId && TeacherAcademicScope::subjectAssignments($user, $termId)->contains(
                fn ($assignment) => (int) $assignment->school_class_id === $classId
                    && (int) $assignment->subject_id === $subjectId
            ));
        abort_unless($allowed, 403);

        return Student::query()->where('school_id', $user->school_id)
            ->where('school_class_id', $classId)->where('status', 'active');
    }

    public static function homework(User $user, int $assignmentId, ?int $studentId = null): HomeworkAssignment
    {
        $assignment = HomeworkAssignment::query()
            ->where('school_id', $user->school_id)->findOrFail($assignmentId);

        if (TeacherAcademicScope::isTeacher($user)) {
            abort_unless(
                $assignment->teacher_id === $user->id
                || TeacherAcademicScope::classIds($user)->contains($assignment->school_class_id),
                403
            );
        } else {
            $student = self::student($user, $studentId);
            abort_unless($assignment->published_at && $assignment->school_class_id === $student->school_class_id, 403);
            abort_if($assignment->stream_id && $assignment->stream_id !== $student->stream_id, 403);
        }

        return $assignment;
    }
}
