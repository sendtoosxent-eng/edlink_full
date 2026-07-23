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
        $permission = match ($route) {
            'students.index' => 'students.view',
            'students.register', 'student-categories.index', 'students.portal-access' => 'students.manage',
            'students.activities' => null,
            'fee-payments.index' => 'finance.payments',
            'expenses.index' => 'finance.expenses',
            'attendance.index' => 'attendance.daily',
            'attendance.subject' => 'attendance.subject',
            'attendance.reports' => 'attendance.reports',
            'classes.index' => 'academics.classes',
            'subjects.index' => 'academics.subjects',
            'timetable.index' => 'academics.timetable',
            'events.index' => 'academics.events',
            'promotions.index' => 'academics.promotions',
            'exams.setup' => 'exams.setup',
            'exams.marks' => 'exams.marks',
            'exams.results' => 'exams.results',
            'staff.index' => 'staff.directory',
            'staff.register' => 'staff.manage',
            'staff.attendance' => 'staff.attendance',
            'payroll.index' => 'staff.payroll',
            'designations.index' => 'staff.designations',
            'parents.index', 'parents.register' => 'parents.manage',
            'communications.index' => 'parents.communications',
            'reports.index', 'reports.student-term-report', 'reports.bulk-term-reports' => 'reports.view',
            default => null,
        };

        $module = match (true) {
            str_starts_with($route, 'students.'), str_starts_with($route, 'student-categories.') => 'students',
            str_starts_with($route, 'fee-'), str_starts_with($route, 'terms.'), str_starts_with($route, 'expenses.') => 'finance',
            str_starts_with($route, 'attendance.') => 'attendance',
            str_starts_with($route, 'classes.'), str_starts_with($route, 'subjects.'), str_starts_with($route, 'grading-scales.'), str_starts_with($route, 'timetable.'), str_starts_with($route, 'events.'), str_starts_with($route, 'promotions.') => 'academics',
            $route === 'workbench.home' => null,
            $route === 'students.activities' => null,
            $route === 'leaves.index' => null,
            str_starts_with($route, 'staff.'), str_starts_with($route, 'payroll.'), str_starts_with($route, 'leaves.'), str_starts_with($route, 'designations.') => 'staff',
            str_starts_with($route, 'exams.'), str_starts_with($route, 'my-results') => 'exams',
            str_starts_with($route, 'parents.'), str_starts_with($route, 'communications.') => 'parents',
            str_starts_with($route, 'reports.') => 'reports',
            str_starts_with($route, 'settings.') => 'settings',
            default => null,
        };

        $user = $request->user();
        abort_unless(! $permission || $user?->hasPermission($permission), 403);
        abort_unless($permission || ! $module || $user?->hasModuleAccess($module), 403);

        return $next($request);
    }
}