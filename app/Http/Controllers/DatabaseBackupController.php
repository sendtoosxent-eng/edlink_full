<?php

namespace App\Http\Controllers;

use App\Models\PlatformAuditLog;
use App\Models\School;
use App\Services\BackupService;
use App\Services\SchoolBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupController extends Controller
{
    public function __invoke(Request $request, BackupService $backups): BinaryFileResponse
    {
        $this->authorizeDownload($request);
        $backup = $backups->create();
        $this->audit($request, 'platform.backup.downloaded', [
            'scope' => 'all_schools',
            'backup_id' => $backup->id,
            'checksum' => $backup->checksum,
        ]);

        return response()->download($backup->path, basename($backup->path), [
            'Content-Type' => str_ends_with($backup->path, '.sqlite') ? 'application/vnd.sqlite3' : 'application/gzip',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            'X-Content-Type-Options' => 'nosniff',
            'X-Backup-SHA256' => $backup->checksum,
        ]);
    }

    public function school(Request $request, SchoolBackupService $backups): BinaryFileResponse
    {
        $this->authorizeDownload($request);
        $data = $request->validate(['school_id' => ['required', 'integer', 'exists:schools,id']]);
        $school = School::findOrFail($data['school_id']);
        $backup = $backups->create($school);

        $this->audit($request, 'platform.backup.downloaded', [
            'scope' => 'school',
            'school_id' => $school->id,
            'school_number' => $school->school_number,
            'checksum' => $backup['checksum'],
            'row_count' => array_sum($backup['row_counts']),
        ]);

        return response()->download($backup['path'], $backup['filename'], [
            'Content-Type' => 'application/gzip',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            'X-Content-Type-Options' => 'nosniff',
            'X-Backup-SHA256' => $backup['checksum'],
        ])->deleteFileAfterSend(true);
    }

    private function authorizeDownload(Request $request): void
    {
        abort_unless(Auth::guard('platform')->check() && $request->session()->get('platform_mfa_passed'), 403);
    }

    private function audit(Request $request, string $event, array $metadata): void
    {
        PlatformAuditLog::create([
            'platform_admin_id' => Auth::guard('platform')->id(),
            'event' => $event,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
