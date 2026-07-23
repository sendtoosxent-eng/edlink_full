<?php
namespace App\Http\Controllers;
use App\Models\LandingPageSetting;
use Illuminate\View\View;
class LandingPageController extends Controller { public function __invoke(): View { return view('welcome', ['landing' => LandingPageSetting::values()]); } }