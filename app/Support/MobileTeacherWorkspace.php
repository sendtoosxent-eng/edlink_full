<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class MobileTeacherWorkspace
{
    public static function forUser(User $user): array
    {
        $term = $user->school->currentTerm();
        $classIds = TeacherAcademicScope::classIds($user);
        $classes = DB::table('school_classes')->where('school_id', $user->school_id)->pluck('name', 'id');
        $subjects = DB::table('subjects')->where('school_id', $user->school_id)->pluck('name', 'id');
        $assignments = TeacherAcademicScope::subjectAssignments($user, $term?->id);
        $tools = [];
        $add = function ($label, $route, $group, $native = null) use (&$tools) {
            $tools[] = compact('label', 'group', 'native') + ['id' => $route, 'path' => route($route, [], false)];
        };
        if (TeacherAcademicScope::canViewStudentDirectory($user)) {
            $add('My class students', 'students.index', 'My classes');
        }
        foreach (self::catalog() as [$label, $route, $permission, $group, $native]) {
            if ($user->hasPermission($permission)) {
                if ($route === 'subject-selections.index' && $user->school->school_type !== 'secondary') continue;
                $add($label, $route, $group, $native);
            }
        }
        $add('Homework & submissions', 'homework.index', 'Teaching', 'homework');
        $add('My leave requests', 'leaves.index', 'My school', 'leave');
        $add('Notifications', 'notifications.index', 'My school', 'notifications');
        $patron = DB::table('student_houses')->where('school_id', $user->school_id)->where('patron_user_id', $user->id)->exists()
            || DB::table('student_clubs')->where('school_id', $user->school_id)->where('patron_user_id', $user->id)->exists();
        if ($patron || $user->hasPermission('students.activities')) $add('Houses & clubs', 'students.activities', 'My school');
        return [
            'role_label' => $classIds->isNotEmpty() ? 'Class teacher' : ($assignments->isNotEmpty() ? 'Subject teacher' : 'Teacher'),
            'term' => $term?->name,
            'class_teacher_classes' => $classIds->map(fn ($id) => ['id' => (int) $id, 'name' => $classes[$id] ?? 'Class'])->values(),
            'subject_assignments' => $assignments->map(fn ($a) => ['school_class_id' => (int) $a->school_class_id, 'class_name' => $classes[$a->school_class_id] ?? 'Class', 'subject_id' => (int) $a->subject_id, 'subject_name' => $subjects[$a->subject_id] ?? 'Subject'])->values(),
            'tools' => collect($tools)->unique('id')->values(),
        ];
    }

    private static function catalog(): array
    {
        return [
            ['Daily class register', 'attendance.index', 'attendance.daily', 'My classes', 'attendance'],
            ['Subject attendance', 'attendance.subject', 'attendance.subject', 'Teaching', 'attendance'],
            ['Attendance reports', 'attendance.reports', 'attendance.reports', 'Reports', null],
            ['Enter & review marks', 'exams.marks', 'exams.marks', 'Teaching', 'add_marks'],
            ['Report cards & results', 'exams.results', 'exams.results', 'Reports', null],
            ['Create exams', 'exams.setup', 'exams.setup', 'Exams', null],
            ['Grading scales', 'grading-scales.index', 'exams.setup', 'Exams', null],
            ['Subjects', 'subjects.index', 'academics.subjects', 'Teaching', null],
            ['Student subject selection', 'subject-selections.index', 'academics.subjects', 'Teaching', null],
            ['Timetable', 'timetable.index', 'academics.timetable', 'Teaching', null],
            ['Events', 'events.index', 'academics.events', 'My school', null],
            ['Classes & streams', 'classes.index', 'academics.classes', 'My classes', null],
            ['Promotions', 'promotions.index', 'academics.promotions', 'My classes', null],
            ['Register students', 'students.register', 'students.manage', 'Students', null],
            ['Student categories', 'student-categories.index', 'students.manage', 'Students', null],
            ['Portal access', 'students.portal-access', 'students.manage', 'Students', null],
            ['Graduates & alumni', 'graduates.index', 'students.view', 'Students', null],
            ['Student ID cards', 'students.id-cards', 'students.view', 'Students', null],
            ['Staff directory', 'staff.index', 'staff.directory', 'Staff', null],
            ['Register staff', 'staff.register', 'staff.manage', 'Staff', null],
            ['Staff attendance', 'staff.attendance', 'staff.attendance', 'Staff', null],
            ['Payroll', 'payroll.index', 'staff.payroll', 'Staff', null],
            ['Designations & access', 'designations.index', 'staff.designations', 'Staff', null],
            ['Parents', 'parents.index', 'parents.manage', 'Parents', null],
            ['Register parent', 'parents.register', 'parents.manage', 'Parents', null],
            ['Communications', 'communications.index', 'parents.communications', 'Parents', null],
            ['Reports', 'reports.index', 'reports.view', 'Reports', null],
            ['Term reports', 'reports.student-term-report', 'reports.view', 'Reports', null],
            ['Bulk term reports', 'reports.bulk-term-reports', 'reports.view', 'Reports', null],
            ['Terms', 'terms.index', 'finance.ledger', 'Finance', null],
            ['Fee structure', 'fee-structures.index', 'finance.ledger', 'Finance', null],
            ['Payments', 'fee-payments.index', 'finance.payments', 'Finance', null],
            ['Expenses', 'expenses.index', 'finance.expenses', 'Finance', null],
            ['Accounting', 'accounting.index', 'accounting.dashboard.view', 'Finance', null],
            ['Reconciliation', 'accounting.reconciliations', 'accounting.reconciliations.manage', 'Finance', null],
            ['Assets', 'accounting.assets', 'accounting.assets.view', 'Finance', null],
            ['Settings', 'settings.index', 'settings.manage', 'Administration', null],
            ['Result access', 'settings.result-access', 'settings.manage', 'Administration', null],
        ];
    }
}
