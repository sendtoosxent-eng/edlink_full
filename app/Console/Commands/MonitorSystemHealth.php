<?php

namespace App\Console\Commands;

use App\Models\PlatformAdmin;
use App\Models\PlatformNotification;
use App\Models\SystemBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonitorSystemHealth extends Command
{
    protected $signature = 'edlink:monitor';

    protected $description = 'Check backups, queues, logs, and storage and notify platform owners.';

    public function handle(): int
    {
        $issues = [];
        $databaseAvailable = true;
        try {
            DB::select('select 1');
        } catch (\Throwable) {
            $databaseAvailable = false;
            $issues[] = 'Database connection failed.';
        }
        if (! is_writable(storage_path())) {
            $issues[] = 'Storage is not writable.';
        }
        if ($databaseAvailable) {
            try {
                if (DB::table('failed_jobs')->count() > 0) {
                    $issues[] = 'One or more queue jobs have failed.';
                }
            } catch (\Throwable) {
                $issues[] = 'Queue health could not be checked.';
            }
            try {
                $last = SystemBackup::where('status', 'verified')->latest('verified_at')->first();
                if (! $last || $last->verified_at?->lt(now()->subDays(2))) {
                    $issues[] = 'No verified backup was created in the last 48 hours.';
                }
            } catch (\Throwable) {
                $issues[] = 'Backup health could not be checked.';
            }
        }
        if (config('edlink.backup.offsite_required') && ! filled(config('edlink.backup.offsite_disk'))) {
            $issues[] = 'Off-site backups are required but not configured.';
        }
        if (file_exists(storage_path('logs/laravel.log')) && filesize(storage_path('logs/laravel.log')) > 10 * 1024 * 1024) {
            $issues[] = 'The application log exceeds 10 MB.';
        }
        if ($issues) {
            Log::critical('Edlink system health check failed.', ['issues' => $issues]);
            if ($databaseAvailable) {
                try {
                    $eventKey = 'health:'.now()->toDateString().':'.hash('sha256', implode('|', $issues));
                    foreach (PlatformAdmin::where('role', 'platform_owner')->where('is_active', true)->get() as $owner) {
                        PlatformNotification::firstOrCreate(['platform_admin_id' => $owner->id, 'event_key' => $eventKey], [
                            'title' => 'Edlink system health alert', 'message' => implode(' ', $issues),
                        ]);
                    }
                } catch (\Throwable $error) {
                    Log::error('Could not create in-app health alerts.', ['error' => $error->getMessage()]);
                }
            }
            $webhook = config('edlink.monitoring.alert_webhook_url');
            if (filled($webhook)) {
                try {
                    Http::timeout(10)->retry(2, 250)->post($webhook, ['service' => config('app.name'), 'environment' => app()->environment(), 'occurred_at' => now()->toIso8601String(), 'issues' => $issues])->throw();
                } catch (\Throwable $error) {
                    Log::error('External health alert failed.', ['error' => $error->getMessage()]);
                }
            }
        }
        $this->info($issues ? implode(' ', $issues) : 'All monitored checks passed.');

        return $issues ? self::FAILURE : self::SUCCESS;
    }
}
