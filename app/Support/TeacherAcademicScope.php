<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class TeacherAcademicScope
{
    public static function isTeacher(User $user): bool
    {
        return $user->role === 'teacher'
            || str_contains(strtolower((string) $user->designation?->name), 'teacher')
            || self::classIds($user)->isNotEmpty()
            || self::subjectAssignments($user, $user->school?->currentTerm()?->id)->isNotEmpty();
    }

    public static function classIds(User $user): Collection
    {
        return DB::table('school_classes')->where('school_id', $user->school_id)
            ->where('class_teacher_user_id', $user->id)->pluck('id');
    }

    public static function subjectAssignments(User $user, ?int $termId = null): Collection
    {
        return DB::table('staff_subjects')->where('school_id', $user->school_id)->where('user_id', $user->id)
            ->when($termId, fn ($query) => $query->where(fn ($scope) => $scope->where('term_id', $termId)->orWhereNull('term_id')))
            ->get(['school_class_id', 'subject_id']);
    }

    public static function canViewStudentDirectory(User $user): bool
    {
        if (! self::isTeacher($user)) {
            return $user->hasPermission('students.view');
        }

        return $user->hasPermission('students.manage') || self::classIds($user)->isNotEmpty();
    }

    public static function canViewAllStudents(User $user): bool
    {
        return ! self::isTeacher($user) || $user->hasPermission('students.manage');
    }

    public static function canEnterPaper(User $user, int $classId, int $subjectId, int $termId): bool
    {
        if (! self::isTeacher($user)) {
            return $user->hasPermission('exams.marks') || $user->hasPermission('exams.setup');
        }

        return self::subjectAssignments($user, $termId)->contains(
            fn ($assignment) => (int) $assignment->school_class_id === $classId && (int) $assignment->subject_id === $subjectId
        );
    }

    public static function canViewExam(User $user, int $classId, int $termId): bool
    {
        if (! self::isTeacher($user)) {
            return $user->hasPermission('exams.results');
        }

        return self::classIds($user)->contains($classId)
            || self::subjectAssignments($user, $termId)->contains(fn ($assignment) => (int) $assignment->school_class_id === $classId);
    }

    public static function grantsMappedPermission(User $user, string $permission): bool
    {
        if (! in_array($permission, ['attendance.daily', 'attendance.subject', 'exams.marks', 'exams.results'], true)) {
            return false;
        }

        $termId = $user->school?->currentTerm()?->id;

        return match ($permission) {
            'attendance.daily' => self::classIds($user)->isNotEmpty(),
            'attendance.subject', 'exams.marks' => self::subjectAssignments($user, $termId)->isNotEmpty(),
            'exams.results' => self::classIds($user)->isNotEmpty() || self::subjectAssignments($user, $termId)->isNotEmpty(),
            default => false,
        };
    }

    public static function grantsMappedModule(User $user, string $module): bool
    {
        return match ($module) {
            'attendance' => self::grantsMappedPermission($user, 'attendance.daily')
                || self::grantsMappedPermission($user, 'attendance.subject'),
            'exams' => self::grantsMappedPermission($user, 'exams.marks')
                || self::grantsMappedPermission($user, 'exams.results'),
            default => false,
        };
    }
}
