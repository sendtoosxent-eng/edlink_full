<?php

namespace App\Http\Controllers;

use App\Models\LandingPageSetting;
use App\Models\PlatformAuditLog;
use App\Services\PublicImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PlatformLandingPageController extends Controller
{
    public function edit(): View
    {
        return view('platform.website.edit', ['landing' => LandingPageSetting::values()]);
    }

    public function update(Request $request, PublicImageStorage $images): RedirectResponse
    {
        $textKeys = array_diff(array_keys(LandingPageSetting::DEFAULTS), LandingPageSetting::ASSET_KEYS);
        $rules = collect($textKeys)->mapWithKeys(fn ($key) => [$key => [
            'nullable',
            'string',
            'max:'.(str_contains($key, 'text') || str_contains($key, 'description') ? 3000 : 255),
        ]])->all();
        foreach (['facebook_url', 'instagram_url', 'x_url', 'linkedin_url', 'youtube_url', 'tiktok_url'] as $key) {
            $rules[$key] = ['nullable', 'url:http,https', 'max:500'];
        }
        foreach (LandingPageSetting::ASSET_KEYS as $key) {
            $rules[$key] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'];
        }
        $data = $request->validate($rules);
        foreach ($textKeys as $key) {
            LandingPageSetting::updateOrCreate(['key' => $key], ['value' => $data[$key] ?? '']);
        }
        foreach (LandingPageSetting::ASSET_KEYS as $key) {
            if (! $request->hasFile($key)) {
                continue;
            }
            $current = LandingPageSetting::where('key', $key)->value('value');
            $newPath = $images->store($request->file($key), 'landing-page');
            LandingPageSetting::updateOrCreate(['key' => $key], ['value' => $newPath]);
            if ($current && str_starts_with($current, 'landing-page/')) {
                $images->deleteReplacement($current, $newPath);
            }
        }
        PlatformAuditLog::create(['platform_admin_id' => Auth::guard('platform')->id(), 'event' => 'platform.website.updated', 'metadata' => ['fields' => array_keys($data)], 'ip_address' => $request->ip(), 'user_agent' => str($request->userAgent() ?? '')->limit(500)->toString() ?: null]);

        return back()->with('status', 'Landing page content was published successfully.');
    }
}
