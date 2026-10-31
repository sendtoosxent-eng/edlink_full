<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AndroidDownloadController extends Controller
{
    public const PATH = 'android-releases/Edlink.apk';

    public function __invoke(): StreamedResponse
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists(self::PATH), 404);

        return $disk->download(self::PATH, 'Edlink.apk', [
            'Content-Type' => 'application/vnd.android.package-archive',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
