<?php

namespace App\Http\Controllers;

use App\Models\LandingPageSetting;
use App\Models\PlatformAuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PlatformLandingPageController extends Controller
{
    public function edit(): View { return view('platform.website.edit', ['landing' => LandingPageSetting::values()]); }

    public function update(Request $request): RedirectResponse
    {
        $textKeys = array_diff(array_keys(LandingPageSetting::DEFAULTS), ['nav_logo','hero_image','feature_image','about_image','footer_logo']);
        $rules = collect($textKeys)->mapWithKeys(fn ($key) => [$key => [
            'nullable',
            'string',
            'max:'.(str_contains($key, 'text') || str_contains($key, 'description') ? 3000 : 255),
        ]])->all();
        foreach (['nav_logo','hero_image','feature_image','about_image','footer_logo'] as $key) $rules[$key] = ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'];
        $data = $request->validate($rules);
        foreach ($textKeys as $key) LandingPageSetting::updateOrCreate(['key'=>$key], ['value'=>$data[$key] ?? '']);
        foreach (['nav_logo','hero_image','feature_image','about_image','footer_logo'] as $key) {
            if (! $request->hasFile($key)) continue;
            $current = LandingPageSetting::where('key',$key)->value('value');
            if ($current && str_starts_with($current,'landing-page/')) Storage::disk('public')->delete($current);
            LandingPageSetting::updateOrCreate(['key'=>$key], ['value'=>$request->file($key)->store('landing-page','public')]);
        }
        PlatformAuditLog::create(['platform_admin_id'=>Auth::guard('platform')->id(),'event'=>'platform.website.updated','metadata'=>['fields'=>array_keys($data)],'ip_address'=>$request->ip(),'user_agent'=>str($request->userAgent() ?? '')->limit(500)->toString() ?: null]);
        return back()->with('status','Landing page content was published successfully.');
    }
}