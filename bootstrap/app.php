<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\CaptureStaffActivity::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureSchoolLicenceIsActive::class);
        $middleware->alias([
            'branch.context' => \App\Http\Middleware\ResolveActiveSchool::class,
            'designation.access' => \App\Http\Middleware\EnsureDesignationAccess::class,
            'active.user' => \App\Http\Middleware\EnsureActiveUser::class,
            'staff.workbench' => \App\Http\Middleware\RedirectPortalUsersFromWorkbench::class,
            'platform.mfa' => \App\Http\Middleware\EnsurePlatformMfa::class,
            'platform.role' => \App\Http\Middleware\EnsurePlatformRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
