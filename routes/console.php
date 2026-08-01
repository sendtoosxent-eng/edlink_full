<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('edlink:expire-demos')->daily();
Schedule::command('edlink:backup --restore-test --keep-days='.config('edlink.backup_retention_days'))->dailyAt('01:30')->withoutOverlapping();
Schedule::command('edlink:monitor')->hourly()->withoutOverlapping();
Schedule::command('model:prune')->dailyAt('02:30');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
