<?php

use App\Jobs\SendWhatsappAnnouncement;
use App\Models\WhatsappDelivery;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Schedule::command('edlink:expire-demos')->daily();
Schedule::command('edlink:backup --restore-test --keep-days='.config('edlink.backup_retention_days'))->dailyAt('01:30')->withoutOverlapping();
Schedule::command('edlink:monitor')->hourly()->withoutOverlapping();
Schedule::command('edlink:renewal-reminders')->dailyAt('08:00')->withoutOverlapping();
Schedule::command('edlink:payroll-reminders')->dailyAt('09:00')->withoutOverlapping();
Schedule::command('edlink:ensure-demo-accounts')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('model:prune')->dailyAt('02:30');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('edlink:dispatch-whatsapp-pending', function () {
    // Only broadcasts already confirmed by a school operator are picked up.
    if (! Schema::hasTable('whatsapp_deliveries')) {
        return;
    }
    $connection = config('queue.default');
    if (in_array(config('queue.connections.'.$connection.'.driver'), ['sync', 'null', null], true)) {
        return;
    }
    WhatsappDelivery::where('status', 'sending')->where('status_at', '<', now()->subMinutes(5))
        ->update(['status' => 'unknown', 'error_code' => 'worker_interrupted', 'status_at' => now()]);
    WhatsappDelivery::with('broadcast')->where('status', 'queued')->whereNull('queued_at')->orderBy('id')->limit(500)->get()->each(function ($delivery) {
        try {
            SendWhatsappAnnouncement::dispatch($delivery->id, (int) $delivery->broadcast->school_id);
            $delivery->update(['queued_at' => now()]);
        } catch (Throwable) { /* Leave pending for the next scheduler run. */
        }
    });
})->purpose('Queue previously confirmed WhatsApp deliveries awaiting a queue connection');
Schedule::command('edlink:dispatch-whatsapp-pending')->everyMinute()->withoutOverlapping();
