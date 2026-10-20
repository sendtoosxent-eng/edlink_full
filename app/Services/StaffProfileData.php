<?php

namespace App\Services;

use App\Models\PayrollRun;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffLeave;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StaffProfileData
{
    public function build(User $staff, User $viewer, ?int $termId, string $period): array
    {
        $staff->load(['school', 'designation']);
        $terms = $staff->school->terms()->orderByDesc('year')->orderByDesc('term_number')->get();
        $term = $termId ? $terms->firstWhere('id', $termId) : ($terms->firstWhere('is_current', true) ?? $terms->first());
        abort_if($termId && ! $term, 404);
        $canEdit = $viewer->hasPermission('staff.manage')
            && (! in_array($staff->role, ['admin', 'superadmin'], true) || in_array($viewer->role, ['admin', 'superadmin'], true));
        $canViewPayroll = $viewer->hasPermission('staff.payroll');
        $canViewAttendance = $canEdit || $viewer->hasPermission('staff.attendance');
        $canViewLeave = $canEdit || $viewer->hasPermission('staff.leaves');
        $currency = (string) SchoolSetting::getValue($staff->school_id, 'currency', 'UGX');
        $from = Carbon::createFromFormat('!Y-m', $period)->startOfMonth();
        $to = $from->copy()->endOfMonth();
        $classRoles = SchoolClass::where('school_id', $staff->school_id)->where('class_teacher_user_id', $staff->id)
            ->withCount(['students' => fn ($query) => $query->where('status', 'active')])->orderBy('name')->get();
        $assignments = DB::table('staff_subjects')->join('subjects', 'subjects.id', '=', 'staff_subjects.subject_id')
            ->leftJoin('school_classes', 'school_classes.id', '=', 'staff_subjects.school_class_id')
            ->where('staff_subjects.school_id', $staff->school_id)->where('staff_subjects.user_id', $staff->id)
            ->where(fn ($query) => $query->where('staff_subjects.term_id', $term?->id ?? 0)->orWhereNull('staff_subjects.term_id'))
            ->orderBy('school_classes.name')->orderBy('subjects.name')
            ->get(['subjects.id as subject_id', 'subjects.name as subject_name', 'subjects.code', 'school_classes.name as class_name', 'staff_subjects.term_id']);
        $timetable = DB::table('timetable_slots')->leftJoin('subjects', 'subjects.id', '=', 'timetable_slots.subject_id')
            ->leftJoin('school_classes', 'school_classes.id', '=', 'timetable_slots.school_class_id')
            ->leftJoin('streams', 'streams.id', '=', 'timetable_slots.stream_id')
            ->where('timetable_slots.school_id', $staff->school_id)->where('timetable_slots.user_id', $staff->id)
            ->where('timetable_slots.term_id', $term?->id ?? 0)->orderBy('starts_at')
            ->get(['day_of_week', 'starts_at', 'ends_at', 'label', 'subjects.name as subject_name', 'school_classes.name as class_name', 'streams.name as stream_name'])
            ->sortBy(fn ($slot) => array_search($slot->day_of_week, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']))->values();
        $attendance = $canViewAttendance ? StaffAttendanceRecord::with('recorder')->where('school_id', $staff->school_id)->where('user_id', $staff->id)
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])->latest('attendance_date')->get() : collect();
        $counts = $attendance->countBy('status');
        $workingRecords = $attendance->whereIn('status', ['present', 'late', 'absent'])->count();
        $attendanceRate = $workingRecords ? round(($attendance->whereIn('status', ['present', 'late'])->count() / $workingRecords) * 100, 1) : null;
        $leaves = $canViewLeave ? StaffLeave::with('approver')->where('school_id', $staff->school_id)->where('user_id', $staff->id)
            ->whereDate('starts_on', '<=', $to)->whereDate('ends_on', '>=', $from)->latest('starts_on')->get() : collect();
        $payrollRuns = $canViewPayroll ? PayrollRun::with(['recordedBy', 'ledgerEntry'])->where('school_id', $staff->school_id)->where('user_id', $staff->id)
            ->where('period', $period)->latest('paid_at')->get() : collect();
        $posted = $payrollRuns->filter(fn ($run) => $run->ledgerEntry?->status === 'posted');
        $salaryPaid = (float) $posted->where('payment_type', 'salary')->sum('amount');
        $advances = (float) $posted->where('payment_type', 'advance')->sum('amount');
        $snapshot = $period !== now()->format('Y-m') ? $posted->first(fn ($run) => $run->salary_snapshot !== null)?->salary_snapshot : null;
        $salaryBasis = (float) ($snapshot ?? $staff->base_salary);
        $remaining = max(0, $salaryBasis - $salaryPaid - $advances);
        $pending = (float) $payrollRuns->filter(fn ($run) => $run->ledgerEntry?->status === 'pending')->sum('amount');

        return compact('staff', 'terms', 'term', 'period', 'canEdit', 'canViewPayroll', 'canViewAttendance', 'canViewLeave', 'currency',
            'classRoles', 'assignments', 'timetable', 'attendance', 'counts', 'attendanceRate', 'leaves', 'payrollRuns', 'salaryPaid', 'advances', 'snapshot', 'salaryBasis', 'remaining', 'pending');
    }
}
