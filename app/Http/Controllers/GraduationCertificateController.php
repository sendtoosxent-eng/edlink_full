<?php

namespace App\Http\Controllers;

use App\Models\GraduationRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class GraduationCertificateController extends Controller
{
    public function __invoke(GraduationRecord $graduationRecord): Response
    {
        abort_unless($graduationRecord->school_id === Auth::user()->school_id && $graduationRecord->reversed_at === null, 404);
        $graduationRecord->load(['school', 'student', 'schoolClass', 'term']);

        return Pdf::loadView('students.graduation-certificate', ['record' => $graduationRecord])
            ->setPaper('a4', 'landscape')
            ->download('graduation-certificate-'.$graduationRecord->student->admission_no.'.pdf');
    }
}
