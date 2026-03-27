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

        // Log::info('[AUTO CLOCK OUT] auto_clock_out_limit_minutes', [
        //     'minutes' => $autoLimitMinutes,
        //    ]);

        if ($autoLimitMinutes <= 0) {
            // Log::warning('[AUTO CLOCK OUT] Disabled (limit <= 0)');
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
        // Log::info('[AUTO CLOCK OUT] Current time', [
        //     'now' => $now->toDateTimeString(),
        // ]);

        // 2. Fetch employees with active time entry
        $employees = User::active()
            ->whereHas('activeTimeEntry')
            ->with('activeTimeEntry')
            ->get();

        // Log::info('[AUTO CLOCK OUT] Active employees found', [
        //     'count' => $employees->count(),
        // ]);

        
        foreach ($employees as $employee) {

            // if ($employee->limit_end_time != 1) {
            //     continue;
            // }

            $entry = $employee->activeTimeEntry;

            if (!$entry) {
                continue;
            }

            //  Check if employee assigned to store
            if (!$employee->store) {
                // Log::warning('[AUTO CLOCK OUT] Employee has no store assigned', [
                //     'employee_id' => $employee->id,
                // ]);
                continue;
            }

            $clockIn = Carbon::parse($entry->clock_in);
            $dayName = $clockIn->format('l');

            // Get store hours for that day
            $storeHours = $employee->store->hoursOfOperation()
                ->where('day_name', $dayName)
                ->first();

            if (!$storeHours || $storeHours->is_closed) {
                // Log::warning('[AUTO CLOCK OUT] Store closed or no hours found', [
                //     'employee_id' => $employee->id,
                //     'day' => $dayName,
                // ]);
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

            // Auto clock trigger time
            $autoClockOutAt = $storeEndTime->copy()
                ->addMinutes($autoLimitMinutes);

            if ($now->lt($autoClockOutAt)) {
                continue;
            }


            //  APPLY ROUNDING
            $payIncrement = TimeTrackerHelper::getTimeTrackerSetting('pay_increments', 5);

           $finalClockOutTime = TimeTrackerHelper::roundNearest(
                $finalClockOutTime,
                (int) $payIncrement
            );

            //  Save auto clock out
            $entry->clock_out = $finalClockOutTime;
            $entry->status = 'completed';
            $entry->updated_at = $finalClockOutTime;
            $entry->save();

            // Log::info('[AUTO CLOCK OUT] Employee auto clocked out', [
            //     'employee_id' => $employee->id,
            //     'clock_out' => $finalClockOutTime->toDateTimeString(),
            // ]);
        }


    }
}
