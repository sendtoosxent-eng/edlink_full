<?php

namespace App\Livewire;

use App\Models\Exam;
use App\Models\SchoolSetting;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class MyResults extends Component
{
    public function render()
    {
        $user = Auth::user();
        $school = $user->school;
        $studentIds = $user->portalStudents()->where('students.school_id', $school->id)->pluck('students.id');

        if ($user->role === 'parent') {
            $studentIds = $studentIds->merge(
                Student::where('school_id', $school->id)
                    ->whereHas('guardians', fn ($query) => $query->where('email', $user->email))
                    ->pluck('id')
            )->unique();
        }

        $students = Student::with(['schoolClass', 'stream'])->where('school_id', $school->id)->whereIn('id', $studentIds)->orderBy('name')->get();
        $publishedExams = Exam::with(['term', 'schoolClass'])
            ->where('school_id', $school->id)
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->get();
        $examsByStudent = $students->mapWithKeys(function (Student $student) use ($publishedExams) {
            $enrolments = $student->enrolments()->get(['term_id', 'school_class_id', 'stream_id'])->keyBy('term_id');

            return [$student->id => $publishedExams->filter(function (Exam $exam) use ($student, $enrolments) {
                $enrolment = $enrolments->get($exam->term_id);
                $classId = $enrolment?->school_class_id ?? $student->school_class_id;
                $streamId = $enrolment?->stream_id ?? $student->stream_id;

                return $exam->school_class_id === $classId && (! $exam->stream_id || $exam->stream_id === $streamId);
            })->values()];
        });

        $feeRule = SchoolSetting::where(['school_id' => $school->id, 'key' => 'results_fee_clearance_required'])->value('value') === 'enabled';

        return view('livewire.my-results', compact('students', 'examsByStudent', 'feeRule'), ['pageTitle' => 'My Results']);
    }
}
