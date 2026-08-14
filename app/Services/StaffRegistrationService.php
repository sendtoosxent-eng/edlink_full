<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class StaffRegistrationService
{
    public function __construct(private readonly StaffNumberGenerator $staffNumbers) {}

    /**
     * Create the staff account and all academic mappings in one transaction.
     * Email delivery deliberately happens after this method returns.
     *
     * @param  array<int, array{class_id:int, subject_id:int}>  $subjectAssignments
     */
    public function register(
        School $school,
        array $attributes,
        ?Term $term,
        ?int $classTeacherClassId,
        array $subjectAssignments,
    ): User {
        if ($subjectAssignments !== [] && ! $term) {
            throw ValidationException::withMessages([
                'teaching_assignments' => 'Open a current term before assigning teaching responsibilities.',
            ]);
        }

        return DB::transaction(function () use ($school, $attributes, $term, $classTeacherClassId, $subjectAssignments): User {
            $classTeacherClass = null;

            if ($classTeacherClassId) {
                $classTeacherClass = SchoolClass::where('school_id', $school->id)
                    ->lockForUpdate()
                    ->find($classTeacherClassId);

                if (! $classTeacherClass) {
                    throw ValidationException::withMessages([
                        'class_teacher_class_id' => 'Choose a class belonging to this school.',
                    ]);
                }

                if ($classTeacherClass->class_teacher_user_id) {
                    throw ValidationException::withMessages([
                        'class_teacher_class_id' => 'That class already has a class teacher. Reassign it from Classes & Streams first.',
                    ]);
                }
            }

            $staff = User::create([
                ...$attributes,
                'school_id' => $school->id,
                'password' => Hash::make($attributes['password']),
            ]);

            $staff->update([
                'staff_number' => $this->staffNumbers->generate($school, $staff),
            ]);

            $classTeacherClass?->update(['class_teacher_user_id' => $staff->id]);

            foreach ($subjectAssignments as $assignment) {
                DB::table('class_subjects')->updateOrInsert(
                    [
                        'school_id' => $school->id,
                        'term_id' => $term->id,
                        'school_class_id' => $assignment['class_id'],
                        'subject_id' => $assignment['subject_id'],
                    ],
                    ['updated_at' => now(), 'created_at' => now()],
                );

                DB::table('staff_subjects')->updateOrInsert(
                    [
                        'school_id' => $school->id,
                        'term_id' => $term->id,
                        'user_id' => $staff->id,
                        'school_class_id' => $assignment['class_id'],
                        'subject_id' => $assignment['subject_id'],
                    ],
                    ['updated_at' => now(), 'created_at' => now()],
                );
            }

            AuditLog::record($school->id, 'staff.registered', $staff, [
                'staff_number' => $staff->staff_number,
                'role' => $staff->role,
                'designation_id' => $staff->designation_id,
                'class_teacher_class_id' => $classTeacherClassId,
                'subject_assignments' => $subjectAssignments,
                'employment_status' => $staff->employment_status,
            ]);

            return $staff->fresh(['designation']);
        });
    }
}
