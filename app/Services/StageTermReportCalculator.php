<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\GradingScale;
use App\Models\Student;
use App\Models\StudentEnrolment;
use App\Models\Term;

class StageTermReportCalculator
{
    public static function student(Student $student, Term $term): array
    {
        $enrolment = StudentEnrolment::with('schoolClass')->where(['school_id' => $student->school_id, 'student_id' => $student->id, 'term_id' => $term->id])->first();
        $class = $enrolment?->schoolClass ?? $student->schoolClass;
        $stage = $class?->education_stage ?? 'kindergarten';
        $settings = StageReportSettings::get($student->school_id, $stage);
        $scales = GradingScale::where('school_id', $student->school_id)->where('education_stage', $stage)->orderByDesc('minimum_percentage')->get();
        $marks = StageAssessmentCalculator::marks($student, $term, $settings)->map(function (array $mark) use ($scales): array {
            $scale = $mark['incomplete'] ? null : $scales->first(fn ($item) => $mark['percentage'] >= $item->minimum_percentage && $mark['percentage'] <= $item->maximum_percentage);
            return $mark + ['grade' => $mark['incomplete'] ? 'Incomplete' : ($scale?->grade ?? '-'), 'points' => $mark['incomplete'] ? null : $scale?->aggregate_points, 'comment' => $mark['incomplete'] ? 'One or more required assessments are missing.' : ($scale?->remark ?? '')];
        });
        $used = $marks->reject(fn ($mark) => $mark['incomplete'])->sortByDesc('percentage')->take($settings['best']);
        $average = $used->count() ? round($used->avg('percentage'), 2) : 0;
        $attendance = AttendanceRecord::where(['school_id' => $student->school_id, 'term_id' => $term->id, 'student_id' => $student->id])->where('session_key', 'daily')->get();
        if ($attendance->isEmpty()) {
            $attendance = AttendanceRecord::where(['school_id' => $student->school_id, 'term_id' => $term->id, 'student_id' => $student->id])->get();
        }
        $overall = $scales->first(fn ($scale) => $average >= (float) $scale->minimum_percentage && $average <= (float) $scale->maximum_percentage);

        return compact('settings', 'marks', 'average', 'class', 'scales') + [
            'stage' => $stage, 'scale_configured' => $scales->isNotEmpty(),
            'passed' => $marks->isNotEmpty() && ! $marks->contains('incomplete', true) && $average >= $settings['pass'],
            'aggregate' => $used->sum(fn ($mark) => (int) ($mark['points'] ?? 0)),
            'attendance_present' => $attendance->whereIn('status', ['present', 'late'])->count(), 'attendance_total' => $attendance->count(),
            'fees' => ['due' => $student->totalDue($term), 'paid' => $student->totalPaid($term), 'balance' => $student->balance($term)],
            'promotion' => $enrolment?->promotion_outcome,
            'teacher_remarks' => $marks->isEmpty() ? 'No approved results are available.' : trim(($overall?->remark ?? 'Keep working consistently.').' Overall result: '.($average >= $settings['pass'] ? 'Pass.' : 'Below the configured pass mark.')),
        ];
    }
}
