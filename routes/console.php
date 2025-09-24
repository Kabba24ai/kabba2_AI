<?php

use App\Jobs\SendSameDayRentalReminderJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule your job
Schedule::job(new \App\Jobs\SendDayBeforeRentalReminderJob())
    ->dailyAt('14:40') // utc time
    ->timezone('America/Chicago')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('send-day-before-rental-reminder-job');

Schedule::job(new SendSameDayRentalReminderJob)
    ->dailyAt('09:30')
    ->timezone('America/Chicago')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('send-same-day-rental-reminder-job');

