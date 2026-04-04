<?php

namespace App\Jobs;

use App\Enums\Communication\SmsType;
use App\Models\Authrise\Submission;
use App\Services\TwilioService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Queue\Queueable;

class SendMeetingReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        \Log::channel('jobs')->info(now()->format('Y-m-d H:i:s') . ' Meeting reminder job started.');

        $twilio = new TwilioService();

        // Admin phone numbers
        $adminPhones = [
            '+918000912126',
            '+16158156734',
            '+919824096016',
            '+919725252582',
        ];

        // Reminder 1: 1 day before — only triggers once at 8:00 AM window
        // Job runs every 30 min, so window is 7:55 AM – 8:25 AM
        $now = now();
        $currentHour   = (int) $now->format('H');
        $currentMinute = (int) $now->format('i');
        $isEightAmWindow = ($currentHour === 7 && $currentMinute >= 55)
                        || ($currentHour === 8 && $currentMinute <= 25);

        if ($isEightAmWindow) {
            $tomorrowStart = $now->clone()->addDay()->startOfDay();
            $tomorrowEnd   = $now->clone()->addDay()->endOfDay();

            $meetingsTomorrow = Submission::where('status', 'completed')
                ->whereNotNull('schedule_datetime')
                ->whereBetween('schedule_datetime', [$tomorrowStart, $tomorrowEnd])
                ->get();

            \Log::channel('jobs')->info("Found {$meetingsTomorrow->count()} meeting(s) scheduled for 1-day reminder.");

            foreach ($meetingsTomorrow as $submission) {
                $scheduleAt = $submission->schedule_datetime instanceof Carbon
                    ? $submission->schedule_datetime
                    : Carbon::parse($submission->schedule_datetime);

                $message = sprintf(
                    "📅 REMINDER: Meeting with %s %s tomorrow at %s | Email: %s | Ref: %s",
                    $submission->first_name,
                    $submission->last_name,
                    $scheduleAt->format('h:i A'),
                    $submission->email,
                    $submission->unique_id
                );

                foreach ($adminPhones as $to) {
                    try {
                        $twilio->sendSms($to, $message, [], [
                            'sms_type' => SmsType::NEW_CUSTOMER_SIGNUP_NOTIFICATION,
                        ]);
                    } catch (\Throwable $e) {
                        \Log::channel('jobs')->error('Failed to send 1-day reminder SMS', [
                            'submission_id' => $submission->id,
                            'admin_phone' => $to,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        // Reminder 2: 2 hours before — 30-min window centered at 2h ahead
        // Meetings starting between 1h55m and 2h25m from now get exactly one reminder
        $windowStart = $now->clone()->addMinutes(115); // 1h55m
        $windowEnd   = $now->clone()->addMinutes(145); // 2h25m

        $meetingsInTwoHours = Submission::where('status', 'completed')
            ->whereNotNull('schedule_datetime')
            ->whereBetween('schedule_datetime', [$windowStart, $windowEnd])
            ->get();

        \Log::channel('jobs')->info("Found {$meetingsInTwoHours->count()} meeting(s) scheduled for 2-hour reminder.");

        foreach ($meetingsInTwoHours as $submission) {
            $scheduleAt = $submission->schedule_datetime instanceof Carbon
                ? $submission->schedule_datetime
                : Carbon::parse($submission->schedule_datetime);

            $minutesAway = (int) $now->diffInMinutes($scheduleAt);

            $message = sprintf(
                "⏰ URGENT: Meeting with %s %s in ~%d minutes at %s | Email: %s | Ref: %s",
                $submission->first_name,
                $submission->last_name,
                $minutesAway,
                $scheduleAt->format('h:i A'),
                $submission->email,
                $submission->unique_id
            );

            foreach ($adminPhones as $to) {
                try {
                    $twilio->sendSms($to, $message, [], [
                        'sms_type' => SmsType::NEW_CUSTOMER_SIGNUP_NOTIFICATION,
                    ]);
                } catch (\Throwable $e) {
                    \Log::channel('jobs')->error('Failed to send 2-hour reminder SMS', [
                        'submission_id' => $submission->id,
                        'admin_phone' => $to,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        \Log::channel('jobs')->info('Meeting reminder job completed.');
    }
}
