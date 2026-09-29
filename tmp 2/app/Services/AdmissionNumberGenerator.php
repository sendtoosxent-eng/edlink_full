<?php

namespace App\Services;

use App\Models\School;
use App\Models\Student;

class AdmissionNumberGenerator
{
    public function generate(School $school): string
    {
        $branchLabel = trim((string) ($school->branch_name ?: $school->name));
        preg_match('/[A-Z0-9]/i', $branchLabel, $match);
        $prefix = strtoupper($match[0] ?? 'S');

        $latestSequence = Student::where('school_id', $school->id)
            ->where('admission_no', 'like', $prefix.'-%')
            ->pluck('admission_no')
            ->map(function (?string $number) use ($prefix): int {
                return preg_match('/^'.preg_quote($prefix, '/').'-(\d+)$/', (string) $number, $parts)
                    ? (int) $parts[1]
                    : 0;
            })
            ->max() ?? 0;

        do {
            $number = $prefix.'-'.str_pad((string) ++$latestSequence, 4, '0', STR_PAD_LEFT);
        } while (Student::where('school_id', $school->id)->where('admission_no', $number)->exists());

        return $number;
    }
}
