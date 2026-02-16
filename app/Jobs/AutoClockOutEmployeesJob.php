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
            // Log::warning('[AUTO CLOCK OUT] Disabled (limit <= 0)');
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




              $entry = $employee->activeTimeEntry;

            if (!$entry || !$employee->shift_end_time) {
                continue;
            }

            $clockIn = Carbon::parse($entry->clock_in);

            // Build shift end using SAME DATE as clock in
            $shiftEnd = $clockIn->copy()
                ->setTimeFromTimeString($employee->shift_end_time);

            $autoClockOutTimeSetting = TimeTrackerHelper::getTimeTrackerSetting(
                    'auto_clock_out_time',
                null
            );

            if (!empty($autoClockOutTimeSetting)) {

                    // Use configured time
                    $finalClockOutTime = $clockIn->copy()
                        ->setTimeFromTimeString($autoClockOutTimeSetting);

                    // Log::info('[AUTO CLOCK OUT] Using fixed auto clock-out time', [
                    //     'employee_id' => $employee->id,
                    //     'configured_time' => $autoClockOutTimeSetting,
                    //     'final_time' => $finalClockOutTime->toDateTimeString(),
                    // ]);

                } else {

                    // Fallback to shift end
                    $finalClockOutTime = $shiftEnd;

                    // Log::info('[AUTO CLOCK OUT] Using shift end time (fallback)', [
                    //     'employee_id' => $employee->id,
                    //     'shift_end' => $shiftEnd->toDateTimeString(),
                    // ]);
                }

            // Add allowed minutes
            $autoClockOutAt = $shiftEnd->copy()->addMinutes($autoLimitMinutes);

            Log::info('[AUTO CLOCK OUT] Timing check', [
                'employee_id' => $employee->id,
                'clock_in' => $clockIn->toDateTimeString(),
                'shift_end' => $shiftEnd->toDateTimeString(),
                'auto_clock_out_at' => $autoClockOutAt->toDateTimeString(),
                'now' => $now->toDateTimeString(),
            ]);

            if ($now->lt($autoClockOutAt)) {
                continue;
            }

            // Auto clock out
            $entry->clock_out = $finalClockOutTime;
            $entry->status = 'completed';
            $entry->updated_at = $finalClockOutTime;
            $entry->save();


            Log::info('[AUTO CLOCK OUT] Employee auto clocked out', [
                'employee_id' => $employee->id,
                'clock_out' => $finalClockOutTime->toDateTimeString(),

                'entry clock_out'=> $entry->clock_out,

                'entry updated_at'=> $entry->updated_at,
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
