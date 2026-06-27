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

class AutoClockOutEmployeesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Log::info('[AUTO CLOCK OUT] Job started');

        // 1. Fetch settings
        $autoLimitMinutes = (int) TimeTrackerHelper::getTimeTrackerSetting(
            'auto_clock_out_limit_minutes',
            0
        );

        Log::info('[AUTO CLOCK OUT] auto_clock_out_limit_minutes', [
            'minutes' => $autoLimitMinutes,
           ]);

        if ($autoLimitMinutes <= 0) {
            Log::warning('[AUTO CLOCK OUT] Disabled (limit <= 0)');
            return;
        }


        $messageTemplate = TimeTrackerHelper::getTimeTrackerSetting(
            'auto_clock_out_message',
            'You were automatically clocked out.'
        );

        $globalLimitEnd = TimeTrackerHelper::getTimeTrackerSetting(
            'limit_end_time_to_shift',
            false
        );


        $twilio = new TwilioService();
        $now = Carbon::now();
        Log::info('[AUTO CLOCK OUT] Current time', [
            'now' => $now->toDateTimeString(),
        ]);

        // 2. Fetch employees with active time entry
        $employees = User::active()
            ->whereHas('activeTimeEntry')
            ->with(['activeTimeEntry.breaks', 'store.hoursOfOperation'])
            ->get();

        Log::info('[AUTO CLOCK OUT] Active employees found', [
            'count' => $employees->count(),
        ]);

        
        foreach ($employees as $employee) {

            Log::info('[AUTO CLOCK OUT] Processing employee', [
                'employee_id' => $employee->id,
                'name' => $employee->full_name,
            ]);

            if ($employee->limit_end_time != 1) {
                Log::info('[AUTO CLOCK OUT] Skipped - limit_end_time disabled', [
                    'employee_id' => $employee->id,
                ]);
                continue;
            }

            $entry = $employee->activeTimeEntry;

            if (!$entry) {
                Log::warning('[AUTO CLOCK OUT] Skipped - no active time entry', [
                    'employee_id' => $employee->id,
                ]);
                continue;
            }

            //  Check if employee assigned to store
            if (!$employee->store) {
                Log::warning('[AUTO CLOCK OUT] Employee has no store assigned', [
                    'employee_id' => $employee->id,
                ]);
                continue;
            }

            $clockIn = Carbon::parse($entry->clock_in);
            $dayName = $clockIn->format('l');

            // Get store hours for that day
            $storeHours = $employee->store->hoursOfOperation()
                ->where('day_name', $dayName)
                ->first();

            if (!$storeHours || $storeHours->is_closed) {
                Log::warning('[AUTO CLOCK OUT] Store closed or no hours found', [
                    'employee_id' => $employee->id,
                    'day' => $dayName,
                ]);
                continue;
            }

            // Build store end datetime
            $storeEndTime = Carbon::parse(
                $clockIn->format('Y-m-d') . ' ' . $storeHours->end_time
            );

            $userLimitEnd = (bool) $employee->limit_end_time;
            $shouldLimitEnd = $globalLimitEnd || $userLimitEnd;


            if ($shouldLimitEnd) {
                // Use store end time
                $finalClockOutTime = $storeEndTime;
            } else {
                // Use current time
                $finalClockOutTime = $now;
            }


            // Check active break before rounding
           $activeBreak = $entry->breaks()
            ->whereNotNull('start_time')
            ->whereNull('end_time')
            ->latest()
            ->first();

            // Auto clock trigger time
            $autoClockOutAt = $storeEndTime->copy()
                ->addMinutes($autoLimitMinutes);

            if ($now->lt($autoClockOutAt)) {
                 Log::info('[AUTO CLOCK OUT] Skipped - not reached auto clock out time', [
                    'employee_id' => $employee->id,
                    'auto_clock_out_at' => $autoClockOutAt->toDateTimeString(),
                ]);
                continue;
            }

            if ($activeBreak) {

                Log::info('[AUTO CLOCK OUT] Active break detected', [
                    'employee_id' => $employee->id,
                    'break_id' => $activeBreak->id,
                    'type' => $activeBreak->type,
                    'start_time' => $activeBreak->start_time,
                ]);

                // Employee never returned from break.
                // Treat break start as the actual clock-out time.
                $finalClockOutTime = Carbon::parse($activeBreak->start_time);

                $activeBreak->delete();

                Log::info('[AUTO CLOCK OUT] Incomplete break removed', [
                    'employee_id' => $employee->id,
                    'break_id' => $activeBreak->id,
                    'final_clock_out' => $finalClockOutTime->toDateTimeString(),
                ]);
            }

            //  APPLY ROUNDING
            $payIncrement = TimeTrackerHelper::getTimeTrackerSetting('pay_increments', 5);

           $finalClockOutTime = TimeTrackerHelper::roundNearest(
                $finalClockOutTime,
                (int) $payIncrement
            );

            Log::info('[AUTO CLOCK OUT] After rounding', [
                'employee_id' => $employee->id,
                'final_clock_out' => $finalClockOutTime->toDateTimeString(),
            ]);


            if ($activeBreak) {

               $systemNote = sprintf(
                    'System Auto Clock-Out: Active %s break was not completed. The break record was removed and the employee was clocked out using the break start time (%s).',
                    ucfirst($activeBreak->type),
                    $finalClockOutTime->format('h:i A')
                );

                if (!empty(trim($entry->notes))) {
                    $entry->notes .= PHP_EOL . PHP_EOL . $systemNote;
                } else {
                    $entry->notes = $systemNote;
                }
                Log::info('[AUTO CLOCK OUT] Note added to time entry', [
                    'employee_id' => $employee->id,
                    'note' => $systemNote,
                ]);
            }

            //  Save auto clock out
            $entry->clock_out = $finalClockOutTime;
            $entry->status = 'completed';
            $entry->updated_at = $finalClockOutTime;
            $entry->save();

          
            //  SEND SMS AFTER AUTO CLOCK OUT

            $phoneNumber = $employee->mobile_phone ?? $employee->phone_number;

            if ($phoneNumber) {
                try {
                    $message = str_replace(
                        ['{name}', '{time}'],
                        [
                            $employee->full_name,
                            $finalClockOutTime->format('h:i A')
                        ],
                        $messageTemplate
                    );

                    $response = $twilio->sendSms($phoneNumber, $message);

                    if ($response['success']) {
                        Log::info('[AUTO CLOCK OUT] SMS sent', [
                            'employee_id' => $employee->id,
                            'phone' => $phoneNumber,
                            'sid' => $response['sid'] ?? null,
                        ]);
                    } else {
                        Log::error('[AUTO CLOCK OUT] SMS failed', [
                            'employee_id' => $employee->id,
                            'phone' => $phoneNumber,
                            'error' => $response['message'],
                        ]);
                    }

                } catch (\Throwable $e) {
                    Log::error('[AUTO CLOCK OUT] SMS failed', [
                        'employee_id' => $employee->id,
                        'phone' => $phoneNumber,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        }

        Log::info('[AUTO CLOCK OUT] Job completed');

    }
}
