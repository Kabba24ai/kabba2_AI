<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

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

// Schedule::command('db:refresh-staging')
//     ->dailyAt('07:00')
//     ->timezone('America/Chicago')
//     ->withoutOverlapping()
//     ->onOneServer()
//     ->name('refresh-staging-database');

Schedule::job(new \App\Jobs\AutoClockOutEmployeesJob())
    ->cron('*/20 * * * *')
    ->timezone('America/Chicago')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('auto-clock-out-employees');

Schedule::job(new \App\Jobs\AutoLunchBreakJob())
    ->cron('*/10 * * * *') // every 10 minutes
    ->timezone('America/Chicago')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('auto-lunch-break');

Schedule::job(new \App\Jobs\SalesFunnelAfterEventJob())
    ->everyFifteenMinutes()
    ->timezone('America/Chicago')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('sales-funnel-after-event-job');

Schedule::job(new \App\Jobs\SalesFunnelBeforeEventJob())
    ->everyFifteenMinutes()
    ->timezone('America/Chicago')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('sales-funnel-before-event-job');



