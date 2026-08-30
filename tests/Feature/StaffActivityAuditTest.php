<?php

use App\Http\Middleware\CaptureStaffActivity;
use App\Livewire\AuditTrail;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

function auditedUser(string $role): User
{
    $school = School::create(['name' => ucfirst($role).' Audit School', 'slug' => $role.'-audit-school-'.uniqid()]);

    return User::factory()->create(['school_id' => $school->id, 'role' => $role]);
}

it('captures an administrator request without storing submitted values', function () {
    $user = auditedUser('admin');
    $request = Request::create('/finance/expenses', 'POST', ['password' => 'secret', 'amount' => '50000']);
    $request->setUserResolver(fn () => $user);

    (new CaptureStaffActivity)->handle($request, fn () => new Response('', 200));

    $log = AuditLog::first();
    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($user->id)
        ->and($log->event)->toBe('request.post')
        ->and($log->metadata['role'])->toBe('admin')
        ->and(json_encode($log->metadata))->not->toContain('secret')
        ->and(json_encode($log->metadata))->not->toContain('50000');
});

it('captures each teacher Livewire action with its component and method', function () {
    $user = auditedUser('teacher');
    $snapshot = json_encode(['memo' => ['name' => 'marks-entry']]);
    $request = Request::create('/livewire/update', 'POST', [
        'components' => [[
            'snapshot' => $snapshot,
            'calls' => [['method' => 'save'], ['method' => '$refresh']],
        ]],
    ]);
    $request->setUserResolver(fn () => $user);

    (new CaptureStaffActivity)->handle($request, fn () => new Response('', 200));

    expect(AuditLog::count())->toBe(1);
    $log = AuditLog::first();
    expect($log->event)->toBe('livewire.action')
        ->and($log->metadata['role'])->toBe('teacher')
        ->and($log->metadata['component'])->toBe('marks-entry')
        ->and($log->metadata['action'])->toBe('save');
});
it('recognises the named Livewire update route and records the real method', function () {
    $user = auditedUser('bursar');
    $request = Request::create('/livewire-abc/update', 'POST', [
        'components' => [[
            'snapshot' => json_encode(['memo' => ['name' => 'fee-payments']]),
            'calls' => [['method' => 'recordPayment']],
        ]],
    ]);
    $route = new Route(['POST'], 'livewire-abc/update', ['as' => 'default-livewire.update', 'uses' => 'Livewire\\Mechanisms\\HandleRequests\\HandleRequests@handleUpdate']);
    $request->setRouteResolver(fn () => $route);
    $request->setUserResolver(fn () => $user);

    (new CaptureStaffActivity)->handle($request, fn () => new Response('', 200));

    $log = AuditLog::first();
    expect($log->event)->toBe('livewire.action')
        ->and($log->metadata['component'])->toBe('fee-payments')
        ->and($log->metadata['action'])->toBe('recordPayment');
});

it('allows only administrators to review the school audit trail', function () {
    $admin = auditedUser('admin');
    AuditLog::create(['school_id' => $admin->school_id, 'user_id' => $admin->id, 'event' => 'page.viewed', 'metadata' => ['route' => 'dashboard']]);
    AuditLog::create(['school_id' => $admin->school_id, 'user_id' => $admin->id, 'event' => 'livewire.action', 'metadata' => ['component' => 'school-settings-v2', 'action' => '$set']]);
    AuditLog::create(['school_id' => $admin->school_id, 'user_id' => $admin->id, 'event' => 'page.viewed', 'metadata' => ['action' => 'App\\Livewire\\PortalAccess', 'path' => '/students/portal-access']]);
    AuditLog::create(['school_id' => $admin->school_id, 'user_id' => $admin->id, 'event' => 'audit_trail.viewed', 'metadata' => ['route' => 'settings.audit-trail']]);

    Livewire::actingAs($admin)->test(AuditTrail::class)
        ->assertSee('Audit Trail')
        ->assertSee('Dashboard')
        ->assertSee('School Settings')
        ->assertSee('Portal Access')
        ->assertSee('Viewed screen')
        ->assertSee('Made a change')
        ->set('search', 'dashboard')
        ->assertSee('Dashboard');

    $teacher = auditedUser('teacher');
    Livewire::actingAs($teacher)->test(AuditTrail::class)->assertForbidden();
});
