<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('union-mail:expire')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('union-mail:fetch-replies')->everyMinute()->withoutOverlapping();
Schedule::command('referendums:close')->everyMinute()->withoutOverlapping();
