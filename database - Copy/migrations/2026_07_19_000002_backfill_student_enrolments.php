<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('students')
            ->whereNotNull('term_id')
            ->whereNotNull('school_class_id')
            ->whereNotNull('student_category_id')
            ->orderBy('id')
            ->chunkById(200, function ($students) {
                foreach ($students as $student) {
                    $exists = DB::table('student_enrolments')
                        ->where('student_id', $student->id)
                        ->where('term_id', $student->term_id)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $feeStructure = DB::table('fee_structures')
                        ->where('school_id', $student->school_id)
                        ->where('school_class_id', $student->school_class_id)
                        ->where('student_category_id', $student->student_category_id)
                        ->where('term_id', $student->term_id)
                        ->first();

                    DB::table('student_enrolments')->insert([
                        'school_id' => $student->school_id,
                        'student_id' => $student->id,
                        'term_id' => $student->term_id,
                        'school_class_id' => $student->school_class_id,
                        'stream_id' => $student->stream_id,
                        'student_category_id' => $student->student_category_id,
                        'fee_structure_id' => $feeStructure?->id,
                        'base_fee_amount' => $feeStructure?->amount ?? 0,
                        'status' => $student->status === 'active' ? 'active' : 'inactive',
                        'enrolled_at' => $student->admission_date ?? substr($student->created_at, 0, 10),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Backfilled rows are historical data and must not be deleted on rollback.
    }
};
