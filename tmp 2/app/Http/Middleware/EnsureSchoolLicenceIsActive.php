<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolLicenceIsActive
{
    public const EXPIRED_MESSAGE = "Your school's licence has expired. Please contact the software vendor to renew access.";
    public const INACTIVE_MESSAGE = "Your school's licence is not active. Please contact the software vendor for assistance.";

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $school = $user->school;
        if ($school && $school->isLicenceUsable() && ! $school->isExpiredDemo()) {
            return $next($request);
        }

        $message = $school && ($school->license_status === 'expired'
            || ($school->license_expires_at && $school->license_expires_at->isPast())
            || $school->isExpiredDemo())
                ? self::EXPIRED_MESSAGE
                : self::INACTIVE_MESSAGE;

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}