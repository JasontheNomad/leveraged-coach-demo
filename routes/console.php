<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Public demo: no Daily.co/R2 accounts to sync or back up — just reset the sample data nightly.
if (config('app.demo_user_email')) {
    Schedule::command('migrate:fresh --seed --force')->dailyAt('08:00');

    return;
}

Schedule::command('sync:recordings')->everyFiveMinutes();
Schedule::command('messages:purge')->dailyAt('03:00');

Schedule::command('backup:run --only-db')->dailyAt('01:00');
Schedule::command('backup:clean')->dailyAt('01:10');
Schedule::command('backup:monitor')->dailyAt('01:20');
