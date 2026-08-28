<?php

namespace App\Http\Controllers;

use App\Models\GraduationRecord;
use App\Models\SchoolSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class GraduationCertificateController extends Controller
{
    public function __invoke(GraduationRecord $graduationRecord): Response
    {
        abort_unless($graduationRecord->school_id === Auth::user()->school_id && $graduationRecord->reversed_at === null, 404);
        $data = $this->documentData($graduationRecord);

        return Pdf::loadView('students.graduation-certificate', $data)
            ->setPaper('a4', 'landscape')
            ->download('graduation-certificate-'.$graduationRecord->student->admission_no.'.pdf');
    }

    public function recommendation(GraduationRecord $graduationRecord): Response
    {
        abort_unless($graduationRecord->school_id === Auth::user()->school_id && $graduationRecord->reversed_at === null, 404);
        $data = $this->documentData($graduationRecord);

        return Pdf::loadView('students.graduate-recommendation', $data)
            ->setPaper('a4')
            ->download('recommendation-'.$graduationRecord->student->admission_no.'.pdf');
    }

    private function documentData(GraduationRecord $record): array
    {
        $record->load(['school', 'student', 'schoolClass', 'term']);
        $settings = SchoolSetting::where('school_id', $record->school_id)->pluck('value', 'key')->all();
        $badgeDataUri = null;
        if ($record->school->badge_path && Storage::disk('public')->exists($record->school->badge_path)) {
            $path = Storage::disk('public')->path($record->school->badge_path);
            $mime = mime_content_type($path) ?: 'image/png';
            $badgeDataUri = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
        }

        return compact('record', 'settings', 'badgeDataUri');
    }
}
