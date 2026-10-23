<?php

namespace App\Support;

use App\Models\User;
use App\Models\ExamPaper;
use App\Models\Student;
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
            $add('My class students', 'students.index', 'My classes', 'class_students');
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
            'on_leave' => $user->isOnLeave(),
            'temporary_covers' => TeacherAcademicScope::covers($user)->with(['leave', 'fromUser'])->get()->map(fn ($cover) => ['staff' => $cover->fromUser?->name, 'starts_on' => $cover->leave->starts_on->toDateString(), 'ends_on' => $cover->leave->ends_on->toDateString()]),
            'class_teacher_classes' => $classIds->map(fn ($id) => ['id' => (int) $id, 'name' => $classes[$id] ?? 'Class'])->values(),
            'subject_assignments' => $assignments->map(fn ($a) => ['school_class_id' => (int) $a->school_class_id, 'class_name' => $classes[$a->school_class_id] ?? 'Class', 'subject_id' => (int) $a->subject_id, 'subject_name' => $subjects[$a->subject_id] ?? 'Subject'])->values(),
            'tools' => collect($tools)->unique('id')->values(),
            'pending_results' => self::pendingResults($user, $term?->id),
            'pending_attendance' => self::pendingAttendance($user, $term?->id, $classIds, $assignments, $classes, $subjects),
        ];
    }

    public static function pendingResults(User $user, ?int $termId): array
    {
        if (! $termId || ! $user->school->currentTerm()?->isOpen() || ! $user->hasPermission('exams.marks')) return [];

        return ExamPaper::with(['exam.schoolClass', 'exam.stream', 'subject'])
            ->whereHas('exam', fn ($query) => $query->where('school_id', $user->school_id)->where('term_id', $termId)->whereNull('published_at'))
            ->whereNotIn('id', DB::table('exam_paper_submissions')->whereIn('status', ['submitted', 'approved'])->select('exam_paper_id'))
            ->where(fn ($query) => $query->whereHas('exam', fn ($exam) => $exam->where('completed_at', '<=', now()))
                ->orWhereExists(fn ($marks) => $marks->selectRaw('1')->from('exam_marks')->whereColumn('exam_marks.exam_paper_id', 'exam_papers.id')->whereNotNull('score')))
            ->get()->filter(fn ($paper) => TeacherAcademicScope::canEnterPaper($user, $paper->exam->school_class_id, $paper->subject_id, $termId))
            ->sortBy(fn ($paper) => $paper->exam->name.'|'.$paper->exam->schoolClass?->name.'|'.$paper->subject?->name)
            ->map(fn ($paper) => [
                'paper_id' => $paper->id, 'exam_name' => $paper->exam->name,
                'class_name' => $paper->exam->schoolClass?->name ?? 'Class', 'stream_name' => $paper->exam->stream?->name,
                'subject_name' => $paper->subject?->name ?? 'Subject',
            ])->values()->all();
    }

    private static function pendingAttendance(User $user, ?int $termId, $classIds, $assignments, $classes, $subjects): array
    {
        if (! $termId || ! $user->school->currentTerm()?->isOpen() || $user->isOnLeave()) return [];
        $records = DB::table('attendance_records')->where('school_id', $user->school_id)
            ->where('term_id', $termId)->whereDate('attendance_date', today())
            ->get(['school_class_id', 'student_id', 'subject_id', 'session_key']);
        $pending = [];
        if ($user->hasPermission('attendance.daily')) {
            foreach ($classIds as $classId) {
                $students = Student::where('school_id', $user->school_id)->where('school_class_id', $classId)->where('status', 'active')->pluck('id');
                $recorded = $records->filter(fn ($row) => (int) $row->school_class_id === (int) $classId && in_array($row->session_key, [null, '', 'daily'], true) && $row->subject_id === null)->pluck('student_id');
                $missing = $students->diff($recorded)->count();
                if ($missing > 0) {
                    $pending[] = ['school_class_id' => (int) $classId, 'subject_id' => null, 'attendance_type' => 'daily', 'label' => $classes[$classId] ?? 'Class', 'remaining' => $missing];
                }
            }
        }
        if ($user->hasPermission('attendance.subject')) {
            foreach (SubjectAttendanceRegisters::forUser($user) as $slot) {
                $session = SubjectAttendanceRegisters::sessionKey($slot);
                $students = SubjectAttendanceRegisters::studentsFor($user, $slot)->pluck('id');
                $sessions = [$session];
                if ($slot->is_assignment_only) $sessions[] = 'subject:'.$slot->subject_id;
                $recorded = $records->filter(fn ($row) => (int) $row->school_class_id === (int) $slot->school_class_id && (int) $row->subject_id === (int) $slot->subject_id && in_array($row->session_key, $sessions, true))->pluck('student_id');
                $missing = $students->diff($recorded)->count();
                if ($missing > 0) {
                    $pending[] = ['school_class_id' => (int) $slot->school_class_id, 'subject_id' => (int) $slot->subject_id, 'attendance_type' => 'subject', 'session_key' => $session, 'remaining' => $missing, 'label' => $slot->subject_name.' · '.$slot->class_name.($slot->starts_at ? ' · '.substr($slot->starts_at, 0, 5) : '')];
                }
            }
        }
        return $pending;
    }

    private static function catalog(): array
    {
        return [
            ['Daily class register', 'attendance.index', 'attendance.daily', 'My classes', 'daily_attendance'],
            ['Subject attendance', 'attendance.subject', 'attendance.subject', 'Teaching', 'subject_attendance'],
            ['Attendance reports', 'attendance.reports', 'attendance.reports', 'Reports', 'attendance_reports'],
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
