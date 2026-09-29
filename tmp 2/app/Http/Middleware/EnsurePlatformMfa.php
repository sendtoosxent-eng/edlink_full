<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('platform')->check()) return redirect()->route('platform.login');
        $lastActivity = (int) $request->session()->get('platform_last_activity', 0);
        if (! $request->session()->get('platform_mfa_passed') || ($lastActivity && now()->timestamp - $lastActivity > 900)) {
            $request->session()->forget(['platform_mfa_passed','platform_last_activity']);
            return redirect()->route('platform.challenge')->withErrors(['code'=>'Your secure session expired. Verify your authenticator again.']);
        }
        $request->session()->put('platform_last_activity', now()->timestamp);
        return $next($request);
    }
}