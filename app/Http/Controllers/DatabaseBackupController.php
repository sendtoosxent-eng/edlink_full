<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        // A SQLite snapshot contains every tenant. Only the separately
        // authenticated, MFA-verified platform guard may download it.
        abort_unless(Auth::guard('platform')->check() && $request->session()->get('platform_mfa_passed'), 403);

        $connection = config('database.default');
        $configuration = config("database.connections.{$connection}");
        abort_unless(($configuration['driver'] ?? null) === 'sqlite', 422, 'Full database-file backups currently require the SQLite database driver.');

        $database = $configuration['database'] ?? null;
        abort_unless(is_string($database) && $database !== '' && File::exists($database), 404, 'The database file could not be found.');

        $directory = storage_path('app/backups');
        File::ensureDirectoryExists($directory);
        $filename = 'edlink-full-database-'.now()->format('Y-m-d_H-i-s').'.sqlite';
        $snapshot = $directory.DIRECTORY_SEPARATOR.$filename;

        if (File::exists($snapshot)) {
            File::delete($snapshot);
        }

        $quotedSnapshot = DB::connection($connection)->getPdo()->quote($snapshot);
        DB::connection($connection)->unprepared("VACUUM INTO {$quotedSnapshot}");

        abort_unless(File::exists($snapshot) && File::size($snapshot) > 0, 500, 'The database backup could not be created.');

        return response()->download($snapshot, $filename, [
            'Content-Type' => 'application/vnd.sqlite3',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }
}
