<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class StudentSubjectSelectionService
{
    public static function classUsesIndividualSelection(?SchoolClass $class): bool
    {
        if (! $class) {
            return false;
        }

        return $class->education_stage === 'advanced_level'
            || ($class->education_stage === 'lower_secondary'
                && in_array((int) $class->sort_order, [3, 4], true));
    }

    public static function typesFor(SchoolClass $class): array
    {
        return $class->education_stage === 'advanced_level'
            ? ['principal' => 'Principal', 'subsidiary' => 'Subsidiary']
            : ['core' => 'Core', 'elective' => 'Elective'];
    }

    public static function constrainStudentsForSubject(
        Builder $query,
        SchoolClass $class,
        int $termId,
        int $subjectId,
    ): Builder {
        if (! self::classUsesIndividualSelection($class)) {
            return $query;
        }

        return $query->where(function (Builder $selectionQuery) use ($termId, $subjectId) {
            $selectionQuery
                ->whereNotExists(function ($subquery) use ($termId) {
                    $subquery->selectRaw('1')
                        ->from('student_subject_selections')
                        ->whereColumn('student_subject_selections.student_id', 'students.id')
                        ->where('student_subject_selections.term_id', $termId);
                })
                ->orWhereExists(function ($subquery) use ($termId, $subjectId) {
                    $subquery->selectRaw('1')
                        ->from('student_subject_selections')
                        ->whereColumn('student_subject_selections.student_id', 'students.id')
                        ->where('student_subject_selections.term_id', $termId)
                        ->where('student_subject_selections.subject_id', $subjectId);
                });
        });
    }

    public static function studentTakesSubject(Student $student, int $termId, int $subjectId): bool
    {
        if (! self::classUsesIndividualSelection($student->schoolClass)) {
            return true;
        }

        $selections = DB::table('student_subject_selections')
            ->where('student_id', $student->id)
            ->where('term_id', $termId);

        return ! (clone $selections)->exists()
            || (clone $selections)->where('subject_id', $subjectId)->exists();
    }
}
