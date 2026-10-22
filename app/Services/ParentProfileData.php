<?php

namespace App\Services;

use App\Models\FeePayment;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\User;
use App\Support\TeacherAcademicScope;

class ParentProfileData
{
    public function build(User $parent, User $viewer, ?int $termId = null): array
    {
        $parent->load('school');
        $learners = $parent->portalStudents()->where('students.school_id', $parent->school_id)
            ->wherePivot('school_id', $parent->school_id)->with(['schoolClass', 'stream'])->orderBy('name')->get();
        $terms = $parent->school->terms()->orderByDesc('year')->orderByDesc('term_number')->get();
        $term = $termId ? $terms->firstWhere('id', $termId) : ($terms->firstWhere('is_current', true) ?? $terms->first());
        abort_if($termId && ! $term, 404);
        $canEdit = $viewer->hasPermission('parents.manage');
        $canViewFinance = $viewer->hasPermission('finance.payments') || $viewer->hasPermission('finance.reports') || $viewer->hasPermission('finance.ledger');
        $profileStudentIds = TeacherAcademicScope::canViewStudentDirectory($viewer)
            ? TeacherAcademicScope::scopeStudents(Student::where('school_id', $parent->school_id), $viewer, $term?->id)->whereIn('id', $learners->pluck('id'))->pluck('id')
            : collect();
        $availableStudents = $canEdit ? Student::where('school_id', $parent->school_id)->with('schoolClass')->orderBy('name')->get() : collect();
        $currency = (string) SchoolSetting::getValue($parent->school_id, 'currency', 'UGX');
        $balances = $canViewFinance && $term ? $learners->map(fn ($student) => [
            'student' => $student, 'due' => $student->totalDue($term),
            'paid' => $student->totalPaid($term), 'balance' => $student->balance($term),
        ]) : collect();
        $payments = $canViewFinance && $term ? FeePayment::posted()->where('school_id', $parent->school_id)
            ->whereIn('student_id', $learners->pluck('id'))->where('term_id', $term->id)
            ->with('student')->latest('paid_at')->limit(30)->get() : collect();
        $portalAvailable = $parent->canLogin();

        return compact('parent', 'learners', 'terms', 'term', 'canEdit', 'canViewFinance', 'profileStudentIds', 'availableStudents', 'currency', 'balances', 'payments', 'portalAvailable');
    }
}
