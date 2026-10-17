<?php

use App\Models\PlatformAdmin;
use App\Models\PlatformAuditLog;
use App\Models\School;
use App\Models\User;
use App\Services\SchoolBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

function backupPlatformOwner(string $role = 'platform_owner'): PlatformAdmin
{
    return PlatformAdmin::create([
        'name' => 'Backup Administrator',
        'email' => $role.'@backups.test',
        'password' => 'StrongPassword!123',
        'role' => $role,
        'is_active' => true,
    ]);
}

it('creates a tenant-scoped archive without another schools records', function () {
    $first = School::create(['name' => 'First School', 'slug' => 'first-school']);
    $second = School::create(['name' => 'Second School', 'slug' => 'second-school']);
    User::factory()->create(['school_id' => $first->id, 'email' => 'first@school.test']);
    User::factory()->create(['school_id' => $second->id, 'email' => 'second@school.test']);

    $backup = app(SchoolBackupService::class)->create($first);
    $json = gzdecode(File::get($backup['path']));
    $payload = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

    expect($payload['format'])->toBe('edlink-school-backup')
        ->and($payload['school']['id'])->toBe($first->id)
        ->and(collect($payload['tables']['schools'])->pluck('id')->all())->toBe([$first->id])
        ->and(collect($payload['tables']['users'])->pluck('email')->all())->toContain('first@school.test')
        ->not->toContain('second@school.test')
        ->and($backup['checksum'])->toBe(hash_file('sha256', $backup['path']));

    File::delete($backup['path']);
});

it('allows only an mfa verified platform owner to download school backups', function () {
    $school = School::create(['name' => 'Download School', 'slug' => 'download-school']);
    $owner = backupPlatformOwner();

    $response = $this->actingAs($owner, 'platform')
        ->withSession(['platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp])
        ->post(route('platform.backups.school.download'), ['school_id' => $school->id]);

    $response->assertOk()
        ->assertHeader('content-type', 'application/gzip')
        ->assertHeader('cache-control', 'no-store, private');

    expect(PlatformAuditLog::where('event', 'platform.backup.downloaded')
        ->where('metadata->school_id', $school->id)->exists())->toBeTrue();

    $operationsAdmin = backupPlatformOwner('operations_admin');
    $this->actingAs($operationsAdmin, 'platform')
        ->withSession(['platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp])
        ->post(route('platform.backups.school.download'), ['school_id' => $school->id])
        ->assertForbidden();
});

it('shows backup download controls to a platform owner', function () {
    $owner = backupPlatformOwner();
    School::create(['name' => 'Selectable School', 'slug' => 'selectable-school']);

    $this->actingAs($owner, 'platform')
        ->withSession(['platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp])
        ->get(route('platform.backups'))
        ->assertOk()
        ->assertSeeText('Download a full database backup')
        ->assertSeeText('Selectable School')
        ->assertSeeText('Download a school-scoped backup');
});
