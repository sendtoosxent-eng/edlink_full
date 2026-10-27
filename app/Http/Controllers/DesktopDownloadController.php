<?php

namespace App\Http\Controllers;

use App\Models\DesktopRelease;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DesktopDownloadController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        $release = DesktopRelease::currentWindows();
        abort_unless($release && $release->hasSafePath(), 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($release->path), 404);

        return $disk->download($release->path, basename($release->filename), [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'X-Checksum-SHA256' => $release->sha256,
        ]);
    }
}
