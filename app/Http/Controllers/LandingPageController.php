<?php

namespace App\Http\Controllers;

use App\Models\DesktopRelease;
use App\Models\LandingPageSetting;
use App\Models\School;
use App\Support\SchoolSubdomain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $subdomain = SchoolSubdomain::fromHost($request->getHost());

        if ($subdomain && School::where('subdomain', $subdomain)->exists()) {
            return redirect('/login');
        }

        $desktopRelease = DesktopRelease::currentWindows();
        if ($desktopRelease && (! $desktopRelease->hasSafePath() || ! Storage::disk('local')->exists($desktopRelease->path))) {
            $desktopRelease = null;
        }

        return view('welcome', ['landing' => LandingPageSetting::values(), 'desktopRelease' => $desktopRelease]);
    }
}
