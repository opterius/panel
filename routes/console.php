<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(\Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote');

// Check alert rules every minute
Schedule::command('alerts:check')->everyMinute();

// Collect server metrics from each agent every minute. Powers the historical
// charts on the server monitor page.
Schedule::command('monitor:collect')->everyMinute()->withoutOverlapping();
