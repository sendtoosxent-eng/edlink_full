<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;

class StudentIdCardController extends Controller
{
    public function __invoke()
    {
        $students = Student::with(['schoolClass', 'stream'])
            ->where('school_id', auth()->user()->school_id)
            ->where('status', 'active')
            // Students are stored with a single `name` column in the current schema.
            ->orderBy('name')
            ->get();

        return Pdf::loadView('students.id-cards', [
            'students' => $students,
            'school' => auth()->user()->school,
        ])->setPaper('a4')->stream('student-id-cards.pdf');
    }
}
