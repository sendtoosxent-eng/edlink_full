<?php

namespace App\Livewire;

use App\Models\Exam;
use App\Models\Student;
use App\Models\SchoolSetting;
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
        $exams = Exam::with(['term', 'schoolClass'])
            ->where('school_id', $school->id)
            ->whereNotNull('published_at')
            ->whereIn('school_class_id', $students->pluck('school_class_id')->unique())
            ->latest('published_at')
            ->get();

        $feeRule = SchoolSetting::where(['school_id' => $school->id, 'key' => 'results_fee_clearance_required'])->value('value') === 'enabled';

        return view('livewire.my-results', compact('students', 'exams', 'feeRule'), ['pageTitle' => 'My Results']);
    }
}
