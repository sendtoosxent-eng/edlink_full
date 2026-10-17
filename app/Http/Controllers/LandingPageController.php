<?php

namespace App\Http\Controllers;

use App\Models\LandingPageSetting;
use App\Models\School;
use App\Support\SchoolSubdomain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $subdomain = SchoolSubdomain::fromHost($request->getHost());

        if ($subdomain && School::where('subdomain', $subdomain)->exists()) {
            return redirect('/login');
        }

        return view('welcome', ['landing' => LandingPageSetting::values()]);
    }
}
