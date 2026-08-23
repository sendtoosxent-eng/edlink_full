<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectPortalUsersFromWorkbench
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->user()?->role, ['parent', 'student'], true)) {
            return redirect()->route('portal.home');
        }

        return $next($request);
    }
}
