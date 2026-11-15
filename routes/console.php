<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

foreach (['on', 'off', 'status'] as $action) {
    Artisan::command('takeover:'.$action, function () use ($action) {
        return $this->call('takeover', ['action' => $action]);
    })->purpose('Simulated takeover: '.$action);
}
