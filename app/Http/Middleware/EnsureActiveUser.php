<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->canLogin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => in_array($user->role, ['student', 'parent'], true)
                    ? 'This account cannot sign in because the linked learner is inactive. Please contact your school administrator.'
                    : 'This account is inactive. Please contact your school administrator.',
            ]);
        }

        return $next($request);
    }
}
