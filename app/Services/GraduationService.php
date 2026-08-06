<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\GraduationRecord;
use App\Models\StudentEnrolment;
use App\Models\Term;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GraduationService
{
    public function graduate(StudentEnrolment $enrolment, Term $term, float $average): GraduationRecord
    {
        if ($term->term_number !== 3) {
            throw ValidationException::withMessages(['sourceTermId' => 'Graduation is allowed only after Term 3.']);
        }

        return DB::transaction(function () use ($enrolment, $term, $average): GraduationRecord {
            $enrolment->loadMissing('student');
            $student = $enrolment->student;
            $balance = max(0, $student->balance($term));
            $certificate = sprintf('EDL-%s-%d-%05d', $student->school_id, $term->year, $student->id);

            $record = GraduationRecord::updateOrCreate(
                ['student_id' => $student->id, 'term_id' => $term->id],
                [
                    'school_id' => $student->school_id,
                    'school_class_id' => $enrolment->school_class_id,
                    'graduation_year' => $term->year,
                    'graduated_at' => $term->closed_at?->toDateString() ?? now()->toDateString(),
                    'final_average' => $average,
                    'outstanding_balance' => $balance,
                    'certificate_number' => $certificate,
                    'portal_access' => 'read_only',
                    'graduated_by' => Auth::id(),
                    'reversed_at' => null,
                    'reversed_by' => null,
                    'reversal_reason' => null,
                ],
            );

            $enrolment->update([
                'promotion_outcome' => 'graduated',
                'status' => 'graduated',
                'exited_at' => $record->graduated_at,
            ]);
            $student->update(['status' => 'graduated', 'stream_id' => null, 'term_id' => $term->id]);

            AuditLog::record($student->school_id, 'student.graduated', $student, [
                'term_id' => $term->id,
                'class_id' => $enrolment->school_class_id,
                'certificate_number' => $certificate,
                'outstanding_balance' => $balance,
            ]);

            return $record;
        });
    }

    public function reverse(GraduationRecord $record, string $reason): void
    {
        DB::transaction(function () use ($record, $reason): void {
            $record->loadMissing('student');
            $record->update(['reversed_at' => now(), 'reversed_by' => Auth::id(), 'reversal_reason' => $reason]);
            StudentEnrolment::where(['student_id' => $record->student_id, 'term_id' => $record->term_id])->update([
                'status' => 'active', 'promotion_outcome' => null, 'exited_at' => null,
            ]);
            $record->student->update([
                'status' => 'active', 'school_class_id' => $record->school_class_id, 'term_id' => $record->term_id,
            ]);
            AuditLog::record($record->school_id, 'student.graduation_reversed', $record->student, [
                'graduation_record_id' => $record->id, 'reason' => $reason,
            ]);
        });
    }
}
