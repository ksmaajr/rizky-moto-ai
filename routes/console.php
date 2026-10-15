<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Persist server metrics even when no dashboard/browser is open.
Schedule::command('server:telemetry:collect')->everyMinute()->withoutOverlapping();
