<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $administrator = Auth::guard('platform')->user();

        abort_unless($administrator && in_array($administrator->role, $roles, true), 403);

        return $next($request);
    }
}
