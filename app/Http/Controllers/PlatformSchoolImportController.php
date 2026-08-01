<?php

namespace App\Http\Controllers;

use App\Models\PlatformAuditLog;
use App\Models\School;
use App\Services\PlatformSchoolImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformSchoolImportController extends Controller
{
    public function students(Request $request, School $school, PlatformSchoolImportService $importer): RedirectResponse
    {
        $file = $request->validate(['file'=>['required','file','mimes:csv,txt','max:5120']])['file'];
        $count = $importer->importStudents($school, $file);
        $this->audit($request, $school, 'platform.students.imported', $count);
        return back()->with('status', number_format($count).' students were imported into '.$school->name.'.');
    }

    public function teachers(Request $request, School $school, PlatformSchoolImportService $importer): RedirectResponse
    {
        $file = $request->validate(['file'=>['required','file','mimes:csv,txt','max:5120']])['file'];
        $count = $importer->importTeachers($school, $file);
        $this->audit($request, $school, 'platform.teachers.imported', $count);
        return back()->with('status', number_format($count).' teachers were imported into '.$school->name.'.');
    }

    public function template(string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['students','teachers'], true), 404);
        $headers = $type === 'students' ? PlatformSchoolImportService::STUDENT_HEADERS : PlatformSchoolImportService::TEACHER_HEADERS;
        $example = $type === 'students'
            ? ['Jane Doe','ADM-1001','Primary 5','Blue','Day','2014-03-12','female',now()->toDateString(),'Mary Doe','Mother','0700000000','mary@example.com']
            : ['John Teacher','john.teacher@example.com','0700000001','Mathematics Teacher','Subject Teacher','ChangeMe123!',now()->toDateString(),'0','active'];
        return response()->streamDownload(function () use ($headers, $example): void {
            $output = fopen('php://output', 'wb'); fputcsv($output, $headers); fputcsv($output, $example); fclose($output);
        }, $type.'-import-template.csv', ['Content-Type'=>'text/csv']);
    }

    private function audit(Request $request, School $school, string $event, int $count): void
    {
        PlatformAuditLog::create([
            'platform_admin_id'=>Auth::guard('platform')->id(),'event'=>$event,
            'metadata'=>['school_id'=>$school->id,'school'=>$school->name,'rows'=>$count],
            'ip_address'=>$request->ip(),'user_agent'=>str($request->userAgent() ?? '')->limit(500)->toString() ?: null,
        ]);
    }
}