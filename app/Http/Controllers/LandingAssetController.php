<?php

namespace App\Http\Controllers;

use App\Models\LandingPageSetting;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LandingAssetController extends Controller
{
    public function __invoke(string $key): StreamedResponse
    {
        abort_unless(in_array($key, LandingPageSetting::ASSET_KEYS, true), 404);
        $path = LandingPageSetting::where('key', $key)->value('value');
        abort_unless($path && str_starts_with($path, 'landing-page/') && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
