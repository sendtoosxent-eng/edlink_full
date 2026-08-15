<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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

    public static function academicClassIds(User $user, ?int $termId = null): Collection
    {
        return self::classIds($user)
            ->merge(self::subjectAssignments($user, $termId)->pluck('school_class_id'))
            ->map(fn ($id) => (int) $id)->unique()->values();
    }

    public static function scopeStudents(Builder $query, User $user, ?int $termId = null): Builder
    {
        if (! self::isTeacher($user) || $user->hasPermission('students.manage')) {
            return $query;
        }

        $classIds = self::academicClassIds($user, $termId);
        if ($classIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $scope) use ($classIds, $termId): void {
            $scope->whereIn('students.school_class_id', $classIds);
            if ($termId) {
                $scope->orWhereHas('enrolments', fn (Builder $enrolment) => $enrolment
                    ->where('term_id', $termId)->whereIn('school_class_id', $classIds));
            }
        });
    }

    public static function scopeAttendance(Builder $query, User $user, ?int $termId = null): Builder
    {
        if (! self::isTeacher($user)) {
            return $query;
        }

        $classIds = self::classIds($user)->map(fn ($id) => (int) $id)->values();
        $assignments = self::subjectAssignments($user, $termId)->groupBy('subject_id');
        if ($classIds->isEmpty() && $assignments->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $allowed) use ($classIds, $assignments): void {
            if ($classIds->isNotEmpty()) {
                $allowed->where(function (Builder $daily) use ($classIds): void {
                    $daily->whereNull('subject_id')->where(function (Builder $classScope) use ($classIds): void {
                        $classScope->whereIn('school_class_id', $classIds)
                            ->orWhere(fn (Builder $legacy) => $legacy->whereNull('school_class_id')
                                ->whereHas('student', fn (Builder $student) => $student->whereIn('school_class_id', $classIds)));
                    });
                });
            }
            foreach ($assignments as $subjectId => $subjectAssignments) {
                $method = $classIds->isEmpty() && (int) $assignments->keys()->first() === (int) $subjectId ? 'where' : 'orWhere';
                $allowed->{$method}(function (Builder $subjectScope) use ($subjectId, $subjectAssignments): void {
                    $subjectScope->where('subject_id', $subjectId)->where(function (Builder $classScope) use ($subjectAssignments): void {
                        $ids = $subjectAssignments->pluck('school_class_id')->map(fn ($id) => (int) $id)->values();
                        $classScope->whereIn('school_class_id', $ids)
                            ->orWhere(fn (Builder $legacy) => $legacy->whereNull('school_class_id')
                                ->whereHas('student', fn (Builder $student) => $student->whereIn('school_class_id', $ids)));
                    });
                });
            }
        });
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
