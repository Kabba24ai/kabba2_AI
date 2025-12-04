<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule your job
// Schedule::job(new \App\Jobs\SendDayBeforeRentalReminderJob())
//     ->dailyAt('15:00')
//     ->timezone('America/Chicago')
//     ->withoutOverlapping()
//     ->onOneServer()
//     ->name('send-day-before-rental-reminder-job');

// Schedule::job(new \App\Jobs\SendSameDayRentalReminderJob())
//     ->dailyAt('07:00')
//     ->timezone('America/Chicago')
//     ->withoutOverlapping()
//     ->onOneServer()
//     ->name('send-same-day-rental-reminder-job');

