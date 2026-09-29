<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CaptureStaffActivity
{
    private const AUDITED_ROLES = ['admin', 'superadmin', 'academic_admin', 'registrar', 'teacher', 'bursar'];

    public function handle(Request $request, Closure $next): Response
    {
        $actor = $request->user();
        $response = $next($request);

        if (! $actor || ! in_array($actor->role, self::AUDITED_ROLES, true) || ! $actor->school_id) {
            return $response;
        }

        foreach ($this->activities($request) as $activity) {
            try {
                AuditLog::create([
                    'school_id' => $actor->school_id,
                    'user_id' => $actor->id,
                    'event' => $activity['event'],
                    'metadata' => array_filter([
                        'role' => $actor->role,
                        'route' => $request->route()?->getName(),
                        'path' => '/'.ltrim($request->path(), '/'),
                        'method' => $request->method(),
                        'component' => $activity['component'] ?? null,
                        'action' => $activity['action'] ?? null,
                        'response_status' => $response->getStatusCode(),
                        'user_agent' => str($request->userAgent() ?? '')->limit(255)->toString() ?: null,
                    ], fn ($value) => $value !== null && $value !== ''),
                    'ip_address' => $request->ip(),
                ]);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $response;
    }

    private function activities(Request $request): array
    {
        if ($request->is('livewire/update') || in_array($request->route()?->getName(), ['livewire.update', 'default-livewire.update'], true) || str_contains((string) $request->route()?->getActionName(), 'HandleRequests')) {
            return $this->livewireActivities($request);
        }

        if ($request->isMethod('GET') && ! $request->route()) {
            return [];
        }

        return [[
            'event' => $request->isMethod('GET') ? 'page.viewed' : 'request.'.strtolower($request->method()),
            'action' => $request->route()?->getActionName(),
        ]];
    }

    private function livewireActivities(Request $request): array
    {
        return collect($request->input('components', []))->flatMap(function (array $component) {
            $snapshot = json_decode($component['snapshot'] ?? '{}', true);
            $name = data_get($snapshot, 'memo.name', 'unknown');

            return collect($component['calls'] ?? [])
                ->filter(fn (array $call) => filled($call['method'] ?? null) && ! in_array($call['method'], ['$refresh', 'render'], true))
                ->map(fn (array $call) => [
                    'event' => 'livewire.action',
                    'component' => $name,
                    'action' => $call['method'],
                ]);
        })->values()->all();
    }
}