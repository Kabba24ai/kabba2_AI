<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule your job
Schedule::job(new \App\Jobs\SendDeliveryDayBeforeRentalReminderJob())
    ->dailyAt('15:00')
    ->timezone('America/Chicago')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('send-delivery-day-before-rental-reminder-job');

Schedule::job(new \App\Jobs\SendDeliverySameDayRentalReminderJob())
    ->dailyAt('07:00')
    ->timezone('America/Chicago')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('send-delivery-same-day-rental-reminder-job');

Schedule::job(new \App\Jobs\SendReturnDayBeforeRentalReminderJob())
    ->dailyAt('15:00')
    ->timezone('America/Chicago')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('send-return-day-before-rental-reminder-job');

Schedule::job(new \App\Jobs\SendReturnSameDayRentalReminderJob())
    ->dailyAt('07:00')
    ->timezone('America/Chicago')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('send-return-same-day-rental-reminder-job');



