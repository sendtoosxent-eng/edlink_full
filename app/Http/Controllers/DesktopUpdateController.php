<?php

namespace App\Http\Controllers;

use App\Models\DesktopRelease;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DesktopUpdateController extends Controller
{
    public function manifest(): Response
    {
        $release = DesktopRelease::currentWindows();
        abort_unless($release && $release->sha512 && $release->hasSafePath() && Storage::disk('local')->exists($release->path), 404);
        // JSON is valid YAML, so electron-updater can parse this as latest.yml.
        // Immutable relative URLs also prevent a newer publication changing a pending download.
        $url = 'files/'.$release->sha256.'/'.$release->filename;

        return response(json_encode([
            'version' => $release->version,
            'files' => [['url' => $url, 'sha512' => $release->sha512, 'size' => $release->size]],
            'path' => $url, 'sha512' => $release->sha512,
            'releaseDate' => $release->published_at->toISOString(),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), 200, [
            'Content-Type' => 'application/yaml', 'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function download(string $checksum, string $filename): BinaryFileResponse
    {
        $release = DesktopRelease::query()->where('platform', 'windows-x64')->where('is_active', true)
            ->where('published_at', '<=', now())->where('sha256', $checksum)->where('filename', $filename)->firstOrFail();
        abort_unless($release->hasSafePath() && Storage::disk('local')->exists($release->path), 404);

        // BinaryFileResponse supports range requests for resumable updater downloads.
        return response()->download(Storage::disk('local')->path($release->path), $release->filename, [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
