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
        $textKeys = array_diff(array_keys(LandingPageSetting::defaults()), LandingPageSetting::assetKeys());
        $rules = collect($textKeys)->mapWithKeys(fn ($key) => [$key => [
            'nullable',
            'string',
            'max:3000',
        ]])->all();
        foreach (config('landing-content.fields', []) as $key => $field) {
            if (($field['type'] ?? '') === 'link') {
                $rules[$key] = ['sometimes', 'required', 'string', 'max:500', function ($attribute, $value, $fail) {
                    if (! preg_match('/\A#[a-zA-Z][a-zA-Z0-9_-]*\z/', $value) && ! preg_match('~\A/(?!/)[^\s\\\\]*\z~', $value) && ! (filter_var($value, FILTER_VALIDATE_URL) && str_starts_with($value, 'https://'))) {
                        $fail('Use a page anchor, local path, or a complete HTTPS link.');
                    }
                }];
            }
        }
        foreach (['facebook_url', 'instagram_url', 'x_url', 'linkedin_url', 'youtube_url', 'tiktok_url'] as $key) {
            $rules[$key] = ['nullable', 'url:http,https', 'max:500'];
        }
        foreach (['android_download_url', 'google_play_url', 'app_store_url'] as $key) {
            $rules[$key] = ['nullable', 'url:https', 'max:500'];
        }
        $rules['support_email'] = ['sometimes', 'required', 'email', 'max:255'];
        $rules['whatsapp'] = ['sometimes', 'required', 'regex:/\A[0-9+ ()-]{7,30}\z/'];
        $rules['phone'] = ['sometimes', 'required', 'regex:/\A[0-9+ ()-]{7,30}\z/'];
        foreach (LandingPageSetting::assetKeys() as $key) {
            $rules[$key] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'];
        }
        $data = $request->validate($rules);
        foreach ($textKeys as $key) {
            if (array_key_exists($key, $data)) {
                LandingPageSetting::updateOrCreate(['key' => $key], ['value' => $data[$key] ?? '']);
            }
        }
        foreach (LandingPageSetting::assetKeys() as $key) {
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
