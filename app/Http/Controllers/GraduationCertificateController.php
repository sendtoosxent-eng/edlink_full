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
        $badgeDataUri = $this->imageDataUri($record->school->badge_path);
        $studentPhotoDataUri = $this->imageDataUri($record->student->photo_path);

        return compact('record', 'settings', 'badgeDataUri', 'studentPhotoDataUri');
    }

    private function imageDataUri(?string $path): ?string
    {
        $disk = Storage::disk('public');
        if (! $path || ! $disk->exists($path)) return null;
        $contents = $disk->get($path);
        $image = @getimagesizefromstring($contents);
        if (! $image || ! in_array($image['mime'], ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true)) return null;

        return 'data:'.$image['mime'].';base64,'.base64_encode($contents);
    }
}
