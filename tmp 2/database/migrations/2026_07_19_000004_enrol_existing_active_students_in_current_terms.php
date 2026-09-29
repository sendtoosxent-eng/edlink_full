<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('students')->where('status', 'active')->whereNotNull('school_class_id')->whereNotNull('student_category_id')->orderBy('id')->each(function ($student) {
            $term = DB::table('terms')->where('school_id', $student->school_id)->where('is_current', true)->first();
            if (! $term || DB::table('student_enrolments')->where('student_id', $student->id)->where('term_id', $term->id)->exists()) {
                return;
            }

            $feeStructure = DB::table('fee_structures')->where('school_id', $student->school_id)->where('school_class_id', $student->school_class_id)->where('student_category_id', $student->student_category_id)->where('term_id', $term->id)->first();

            DB::table('student_enrolments')->insert([
                'school_id' => $student->school_id, 'student_id' => $student->id, 'term_id' => $term->id,
                'school_class_id' => $student->school_class_id, 'stream_id' => $student->stream_id,
                'student_category_id' => $student->student_category_id, 'fee_structure_id' => $feeStructure?->id,
                'base_fee_amount' => $feeStructure?->amount ?? 0, 'status' => 'active',
                'enrolled_at' => $student->admission_date ?? substr($student->created_at, 0, 10),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            DB::table('students')->where('id', $student->id)->update(['term_id' => $term->id]);
        });
    }

    public function down(): void
    {
        // Historical enrolments must remain intact.
    }
};
