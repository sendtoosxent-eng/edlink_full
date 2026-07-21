<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDesignationAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route()?->getName() ?? '';
        $module = match (true) {
            str_starts_with($route, 'students.'), str_starts_with($route, 'student-categories.') => 'students',
            str_starts_with($route, 'fee-'), str_starts_with($route, 'terms.'), str_starts_with($route, 'expenses.') => 'finance',
            str_starts_with($route, 'attendance.') => 'attendance',
            str_starts_with($route, 'classes.'), str_starts_with($route, 'subjects.'), str_starts_with($route, 'grading-scales.'), str_starts_with($route, 'timetable.'), str_starts_with($route, 'events.'), str_starts_with($route, 'promotions.') => 'academics',
            $route === 'workbench.home' => null,
            str_starts_with($route, 'staff.'), str_starts_with($route, 'payroll.'), str_starts_with($route, 'leaves.'), str_starts_with($route, 'designations.') => 'staff',
            str_starts_with($route, 'exams.'), str_starts_with($route, 'my-results.') => 'exams',
            str_starts_with($route, 'parents.'), str_starts_with($route, 'communications.') => 'parents',
            str_starts_with($route, 'reports.') => 'reports',
            str_starts_with($route, 'settings.') => 'settings',
            default => null,
        };

        abort_unless(! $module || $request->user()?->hasModuleAccess($module), 403);

        return $next($request);
    }
}
