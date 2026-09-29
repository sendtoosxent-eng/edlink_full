<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentEnrolment;
use App\Models\Term;

class PromotionStatement
{
    public static function for(Student $student, Term $term, ?StudentEnrolment $enrolment = null): string
    {
        $enrolment ??= StudentEnrolment::where([
            'school_id' => $student->school_id,
            'student_id' => $student->id,
            'term_id' => $term->id,
        ])->first();
        $outcome = $enrolment?->promotion_outcome;

        if (! $outcome) {
            return 'Promotion decision pending.';
        }
        if ($outcome === 'graduated') {
            return 'The learner has successfully completed the final class and graduated.';
        }
        if ($outcome === 'withdrawn') {
            return 'The learner has withdrawn from the school.';
        }

        $nextEnrolment = StudentEnrolment::with(['schoolClass', 'term'])
            ->where('school_id', $student->school_id)
            ->where('student_id', $student->id)
            ->whereHas('term', fn ($query) => $query
                ->where('year', '>', $term->year)
                ->orWhere(fn ($later) => $later->where('year', $term->year)->where('term_number', '>', $term->term_number)))
            ->get()->sortBy(fn (StudentEnrolment $item) => sprintf('%08d-%03d', $item->term?->year ?? 0, $item->term?->term_number ?? 0))->first();
        $class = $nextEnrolment?->schoolClass?->name;

        return match ($outcome) {
            'promoted' => $class ? "Promoted to {$class}." : 'Promoted to the next class.',
            'repeated' => $class ? "Will repeat {$class}." : 'The learner will repeat the current class.',
            default => 'Promotion decision pending.',
        };
    }
}
