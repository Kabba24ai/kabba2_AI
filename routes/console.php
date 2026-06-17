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

Schedule::job(new \App\Jobs\AutoLunchReminderJob())
    ->everyFiveMinutes()
    ->timezone('America/Chicago')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('auto-lunch-reminder');

// Schedule::job(new \App\Jobs\UpdateCustomerStatusJob())
//     ->daily()
//     ->timezone('America/Chicago')
//     ->withoutOverlapping()
//     ->onOneServer()
//     ->name('update-customer-status-job');

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

Schedule::job(new \App\Jobs\SendMeetingReminderJob())
    ->everyThirtyMinutes()
    ->timezone('America/Chicago')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('send-meeting-reminder-job');

Schedule::job(new \App\Jobs\SendDailyPendingTermsReminderJob())
    ->dailyAt('06:45')
    ->timezone('America/Chicago')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('send-daily-pending-terms-reminder-job');

// Dispatch AI — 2:00 AM draft (pre-day planning)
Schedule::call(function () {
    $settings = \App\Models\Dispatch\DispatchAiSettings::instance();
    if (!$settings->ai_enabled) {
        return;
    }
    \App\Jobs\BuildDispatchDraftJob::dispatch(0, 'cron', (int) $settings->look_ahead_days);
})
->dailyAt('02:00')
->timezone('America/Chicago')
->name('dispatch-ai-draft-2am')
->withoutOverlapping()
->onOneServer();

// Dispatch AI — 6:30 AM draft (pre-shift refresh)
Schedule::call(function () {
    $settings = \App\Models\Dispatch\DispatchAiSettings::instance();
    if (!$settings->ai_enabled) {
        return;
    }
    \App\Jobs\BuildDispatchDraftJob::dispatch(0, 'cron', (int) $settings->look_ahead_days);
})
->dailyAt('06:30')
->timezone('America/Chicago')
->name('dispatch-ai-draft-630am')
->withoutOverlapping()
->onOneServer();



