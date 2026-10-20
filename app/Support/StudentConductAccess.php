<?php

namespace App\Support;

use App\Models\Student;
use App\Models\User;

class StudentConductAccess
{
    public static function canManage(User $user, Student $student): bool
    {
        return $user->school_id === $student->school_id
            && (in_array($user->role, ['admin', 'superadmin'], true)
                || (! in_array($user->role, ['parent', 'student'], true)
                    && $student->schoolClass?->class_teacher_user_id === $user->id));
    }

    public static function canView(User $user, Student $student): bool
    {
        if ($user->school_id !== $student->school_id || in_array($user->role, ['parent', 'student'], true)) {
            return false;
        }
        if (self::canManage($user, $student)) {
            return true;
        }
        if (TeacherAcademicScope::isTeacher($user) && ! $user->hasPermission('students.manage')) {
            return TeacherAcademicScope::scopeStudents(Student::query(), $user, $user->school->currentTerm()?->id)->whereKey($student->id)->exists();
        }
        return $user->hasPermission('students.view') || $user->hasPermission('students.manage');
    }
}
