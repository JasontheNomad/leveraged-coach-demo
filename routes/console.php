<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sync:recordings')->everyFiveMinutes();
Schedule::command('messages:purge')->dailyAt('03:00');

Schedule::command('backup:run --only-db')->dailyAt('01:00');
Schedule::command('backup:clean')->dailyAt('01:10');
Schedule::command('backup:monitor')->dailyAt('01:20');
