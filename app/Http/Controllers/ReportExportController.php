<?php

namespace App\Http\Controllers;

use App\Models\ReportExport;
use Illuminate\Support\Facades\Storage;

class ReportExportController extends Controller
{
    public function __invoke(ReportExport $reportExport)
    {
        $user = auth()->user();
        abort_unless($reportExport->user_id === $user->id && $reportExport->school_id === $user->school_id, 404);
        abort_unless($reportExport->status === 'completed' && $reportExport->path && ! $reportExport->expires_at?->isPast(), 404);
        abort_unless(Storage::disk($reportExport->disk)->exists($reportExport->path), 404);

        return Storage::disk($reportExport->disk)->download($reportExport->path, $reportExport->filename, [
            'Content-Type'=>'text/csv; charset=UTF-8', 'Cache-Control'=>'private, no-store', 'X-Content-Type-Options'=>'nosniff',
        ]);
    }
}
