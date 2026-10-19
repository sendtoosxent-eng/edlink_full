<?php

use App\Models\SystemBackup;
use App\Services\BackupService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Schema::create('system_backups', function (Blueprint $table): void {
        $table->id();
        $table->string('disk')->default('local');
        $table->string('path');
        $table->unsignedBigInteger('size')->default(0);
        $table->string('checksum', 64);
        $table->string('status')->default('verified');
        $table->timestamp('verified_at')->nullable();
        $table->timestamp('restored_tested_at')->nullable();
        $table->text('failure')->nullable();
        $table->timestamps();
    });
});

afterEach(function (): void {
    collect(SystemBackup::all())->each(function (SystemBackup $backup): void {
        File::delete($backup->path);
    });
    Schema::dropIfExists('system_backups');
});

it('copies a verified database backup to the configured off-site disk', function () {
    Storage::fake('offsite');
    config()->set('edlink.backup.offsite_disk', 'offsite');
    config()->set('edlink.backup.offsite_required', true);
    config()->set('edlink.backup.offsite_prefix', 'production/database');

    $backup = app(BackupService::class)->create();

    expect($backup->status)->toBe('verified');
    Storage::disk('offsite')->assertExists('production/database/'.basename($backup->path));
});

it('fails closed when off-site backup storage is required but not configured', function () {
    config()->set('edlink.backup.offsite_disk', null);
    config()->set('edlink.backup.offsite_required', true);

    expect(fn () => app(BackupService::class)->create())
        ->toThrow(RuntimeException::class, 'Off-site backups are required');

    expect(SystemBackup::latest('id')->first()?->status)->toBe('failed');
});
