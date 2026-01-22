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

class AutoClockOutEmployeesJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('[AUTO CLOCK OUT] Job started');

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


        $twilio = new TwilioService();
        $now = Carbon::now();
        Log::info('[AUTO CLOCK OUT] Current time', [
            'now' => $now->toDateTimeString(),
        ]);

        // 2. Fetch employees with active time entry
        $employees = User::active()
            ->whereNotNull('shift_end_time')
            ->whereHas('activeTimeEntry')
            ->with('activeTimeEntry')
            ->get();

        Log::info('[AUTO CLOCK OUT] Active employees found', [
            'count' => $employees->count(),
        ]);

        foreach ($employees as $employee) {

            Log::info('[AUTO CLOCK OUT] Checking employee', [
                'employee_id' => $employee->id,
                'name' => $employee->full_name,
                'shift_end_time' => $employee->shift_end_time,
            ]);

            $shiftEnd = Carbon::parse($employee->shift_end_time)
                ->setDateFrom($now);

            $clockIn = Carbon::parse($employee->activeTimeEntry->clock_in);

            // Overnight shift ONLY if clock-in was on a previous day
            if ($clockIn->toDateString() < $shiftEnd->toDateString()) {
                $shiftEnd->addDay();

                Log::info('[AUTO CLOCK OUT] Overnight shift detected', [
                    'employee_id' => $employee->id,
                ]);
            }


            $autoClockOutAt = $shiftEnd->copy()->addMinutes($autoLimitMinutes);

            Log::info('[AUTO CLOCK OUT] Timing check', [
                'employee_id' => $employee->id,
                'shift_end' => $shiftEnd->toDateTimeString(),
                'auto_clock_out_at' => $autoClockOutAt->toDateTimeString(),
            ]);

            // 3. Check if we passed auto clock-out time
            if ($now->lessThan($autoClockOutAt)) {
                Log::info('[AUTO CLOCK OUT] Not yet time to auto clock out', [
                    'employee_id' => $employee->id,
                ]);
                continue;
            }

            $entry = $employee->activeTimeEntry;

            if (!$entry) {
                Log::warning('[AUTO CLOCK OUT] Active entry missing', [
                    'employee_id' => $employee->id,
                ]);
                continue;
            }

            // 4. Perform auto clock-out
            $entry->update([
                'clock_out' => $shiftEnd,
                'status' => 'completed',
            ]);

            Log::info('[AUTO CLOCK OUT] Employee auto clocked out', [
                'employee_id' => $employee->id,
                'time_entry_id' => $entry->id,
                'clock_out' => $shiftEnd->toDateTimeString(),
            ]);

            //  SEND SMS

            // $phoneNumber = $employee->mobile_phone ?? $employee->phone_number;

            // if (!$phoneNumber) {
            //     Log::warning('[AUTO CLOCK OUT] No phone number found', [
            //         'employee_id' => $employee->id,
            //     ]);
            //     continue;
            // }

            // try {
            //     $smsResponse = $twilio->sendSms(
            //         $phoneNumber,
            //         $messageTemplate
            //     );

            //     Log::info('[AUTO CLOCK OUT] SMS sent', [
            //         'employee_id' => $employee->id,
            //         'phone' => $phoneNumber,
            //         'twilio_sid' => $smsResponse['sid'] ?? null,
            //     ]);

            // } catch (\Throwable $e) {
            //     Log::error('[AUTO CLOCK OUT] SMS failed', [
            //         'employee_id' => $employee->id,
            //         'phone' => $phoneNumber,
            //         'error' => $e->getMessage(),
            //     ]);
            // }
            
            
        }

        Log::info('[AUTO CLOCK OUT] Job finished');
    }
}
