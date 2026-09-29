<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveActiveSchool
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession(true)) return $next($request);
        try {
            $session = $request->session();
        } catch (\RuntimeException) {
            return $next($request);
        }
        $user = $request->user();
        if (! $user) return $next($request);
        if (! $session->has('active_school_id')) return $next($request);

        $access = $user->schoolAccesses()->whereKey((int) $session->get('active_school_id'))->first();
        if (! $access) {
            $session->forget('active_school_id');
            return $next($request);
        }
        $user->setAttribute('school_id', $access->id);
        $user->setAttribute('role', $access->pivot->role);
        $user->setAttribute('designation_id', $access->pivot->designation_id);
        $user->unsetRelation('school');
        $user->unsetRelation('designation');

        return $next($request);
    }
}
