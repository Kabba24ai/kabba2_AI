<?php

namespace App\Jobs;

use App\Models\Iam\Personnel\User;
use App\Helpers\TimeTrackerHelper;
use App\Services\TwilioService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoLunchReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('[AUTO LUNCH REMINDER] Job started');

        $lunchMinutes = (int) TimeTrackerHelper::getTimeTrackerSetting(
            'auto_lunch_minutes',
            60
        );

        $default_lunch_duration_minutes = (int) TimeTrackerHelper::getTimeTrackerSetting(
            'default_lunch_duration_minutes',
            60
        );

        if ($lunchMinutes <= 0) {
            Log::warning('[AUTO LUNCH REMINDER] Disabled (minutes <= 0)');
            return;
        }

        $messageTemplate = TimeTrackerHelper::getTimeTrackerSetting(
            'auto_lunch_message',
            'Hi {name}, No Lunch Clock-out detected. Update before Shift end or a {default_lunch_time} min Lunch will be applied.'
        );

        $now = Carbon::now();
        $twilio = new TwilioService();

        Log::info('[AUTO LUNCH REMINDER] Settings loaded', [
            'lunch_minutes' => $lunchMinutes,
            'current_time' => $now->toDateTimeString(),
        ]);

        $employees = User::active()
            ->whereHas('activeTimeEntry')
            ->with([
                'store.hoursOfOperation',
                'activeTimeEntry.breaks',
            ])
            ->get();

        Log::info('[AUTO LUNCH REMINDER] Active employees found', [
            'count' => $employees->count(),
        ]);

        foreach ($employees as $employee) {
            try {

                $entry = $employee->activeTimeEntry;

                if (!$entry || $entry->clock_out) {
                    continue;
                }

                //  Skip if lunch already taken
                $hasLunchBreak = $entry->breaks
                    ->where('type', 'lunch')
                    ->isNotEmpty();

                if ($hasLunchBreak) {
                    continue;
                }

                //  Skip if already sent
                if ($entry->lunch_reminder_sent === true) {
                    continue;
                }

                $store = $employee->store;
                if (!$store) continue;

                $dayName = $now->format('l');

                $storeHours = $store->hoursOfOperation
                    ->firstWhere('day_name', $dayName);

                if (!$storeHours || $storeHours->is_closed) {
                    continue;
                }

                //  Store end time
                $storeEnd = Carbon::parse(
                    $now->toDateString() . ' ' . $storeHours->end_time
                );

                //  Trigger time
                $triggerTime = $storeEnd->copy()->subMinutes($lunchMinutes);

                Log::info('[AUTO LUNCH REMINDER] Timing check', [
                    'employee_id' => $employee->id,
                    'store_end' => $storeEnd->toDateTimeString(),
                    'trigger_at' => $triggerTime->toDateTimeString(),
                    'now' => $now->toDateTimeString(),
                ]);

              

                    //  SIMPLE RULE: only after trigger time
                    if ($now->lt($triggerTime)) {
                        Log::info('[AUTO LUNCH REMINDER] Skipped - before trigger time', [
                            'employee_id' => $employee->id,
                            'now' => $now->toDateTimeString(),
                            'trigger' => $triggerTime->toDateTimeString(),
                        ]);
                        continue;
                    }

                $phoneNumber = $employee->mobile_phone ?: $employee->phone_number;
                if (!$phoneNumber) continue;

                //  Message
                $message = str_replace(
                    ['{name}', '{default_lunch_time}'],
                    [$employee->full_name, $default_lunch_duration_minutes],
                    $messageTemplate
                );

                Log::info('[AUTO LUNCH REMINDER] Sending SMS', [
                    'employee_id' => $employee->id,
                    'phone' => $phoneNumber,
                    'message'=>$message,
                ]);

                $response = $twilio->sendSms($phoneNumber, $message);

                if (!empty($response['success'])) {

                    Log::info('[AUTO LUNCH REMINDER] SMS sent', [
                        'employee_id' => $employee->id,
                    ]);

                    //  MARK AS SENT (IMPORTANT)
                    $entry->lunch_reminder_sent = true;
                    $entry->save();

                } else {
                    Log::error('[AUTO LUNCH REMINDER] SMS failed', [
                        'employee_id' => $employee->id,
                        'error' => $response['message'] ?? 'Unknown',
                    ]);
                }

            } catch (\Throwable $e) {
                Log::error('[AUTO LUNCH REMINDER] Exception', [
                    'employee_id' => $employee->id ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('[AUTO LUNCH REMINDER] Job completed');
    }
}