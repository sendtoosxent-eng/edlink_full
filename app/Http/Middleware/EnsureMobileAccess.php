<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_if($user->employment_status === 'inactive', 403, 'This account is inactive.');
        abort_unless($user->hasVerifiedEmail(), 403, 'Verify your email address before using the mobile application.');
        abort_unless(in_array($user->role, ['teacher', 'student', 'parent'], true), 403, 'This account is not enabled for the mobile application.');
        abort_unless($user->school?->isLicenceUsable() && ! $user->school->isExpiredDemo(), 403, 'This school is not currently active.');
        abort_unless($user->tokenCan('mobile'), 403, 'This token cannot access the mobile application.');

        return $next($request);
    }
}
