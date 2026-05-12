<?php

namespace App\Jobs;

use App\Models\Iam\Personnel\User;
use App\Helpers\TimeTrackerHelper;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoLunchBreakJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $now = Carbon::now();

        
        Log::info('================ AUTO LUNCH JOB START ================', [
            'time' => $now->toDateTimeString(),
        ]); 


        $lunchDuration = (int) TimeTrackerHelper::getTimeTrackerSetting(
            'default_lunch_duration_minutes',
            30
        );

        $employees = User::active()
            ->whereHas('activeTimeEntry')
            ->with([
                'activeTimeEntry.breaks',
                'activeTimeEntry.activeBreak',
                'store.hoursOfOperation'
            ])
            ->get();


            Log::info('[AUTO LUNCH] Employees fetched', [
                        'count' => $employees->count(),
                    ]);

            
        foreach ($employees as $employee) {


            Log::info('---------------- EMPLOYEE START ----------------', [
                'employee_id' => $employee->id,
                'name' => $employee->full_name,
            ]);

             //  DEBUG OVERRIDE VALUE
            Log::info('[AUTO LUNCH CHECK]', [
                'employee_id' => $employee->id,
                'lunch_override_raw' => $employee->lunch_override,
                'type' => gettype($employee->lunch_override),
                'casted_int' => (int) $employee->lunch_override,
            ]);


               //  STRICT OVERRIDE CHECK
            if ((int) $employee->lunch_override === 1) {

                Log::warning(' [AUTO LUNCH BLOCKED - OVERRIDE ENABLED]', [
                    'employee_id' => $employee->id,
                ]);

                continue;
            }



            $entry = $employee->activeTimeEntry;

            if (!$entry || !$employee->store) {
                Log::warning('[AUTO LUNCH] Skipped - no entry or store', [
                    'employee_id' => $employee->id,
                ]);
                continue;
            }

            $store = $employee->store;

            $dayName = $now->format('l');

            $storeHours = $store->hoursOfOperation
                ->firstWhere('day_name', $dayName);

                Log::info('[DEBUG STORE HOURS]', [
                    'employee_id' => $employee->id,
                    'day' => $dayName,
                    'storeHours_exists' => !!$storeHours,
                    'is_closed' => $storeHours->is_closed ?? null,
                    'is_lunch_required' => $storeHours->is_lunch_required ?? null,
                ]);

          
           if (
                !$storeHours ||
                $storeHours->is_closed ||
                (int) $storeHours->is_lunch_required !== 1
            ) {
                Log::info('[AUTO LUNCH] Skipped - lunch not required', [
                    'employee_id' => $employee->id,
                    'day' => $dayName,
                    'is_lunch_required' => $storeHours->is_lunch_required ?? null,
                ]);

                continue;
            }

    

            $today = $now->toDateString();

         
            // Get shift end time
                $storeEnd = Carbon::parse(
                    $today . ' ' . $storeHours->end_time
                );

                // Get auto lunch minutes (same as reminder job)
                $lunchMinutes = (int) TimeTrackerHelper::getTimeTrackerSetting(
                    'auto_lunch_minutes',
                    60
                );

                // Calculate lunch start time
                $lunchStartTime = $storeEnd->copy()->subMinutes($lunchMinutes);


            // DEBUG  (VERY IMPORTANT)
            Log::info('[AUTO LUNCH TIMING]', [
                'employee_id' => $employee->id,
                'now' => $now->toDateTimeString(),
                'shift_end' => $storeEnd->toDateTimeString(),
                'trigger_time' => $lunchStartTime->toDateTimeString(),
            ]);

            // Already had lunch
            $alreadyHadLunch = $entry->breaks()
            ->where('type', 'lunch')
            ->whereDate('start_time', $today)
            ->exists();

            // START
            if (!$alreadyHadLunch && !$entry->activeBreak) {

             if ($now->lt($lunchStartTime)) {

                Log::info('[AUTO LUNCH] Waiting for trigger', [
                    'employee_id' => $employee->id,
                ]);

                continue;
            }


                    // $entry->breaks()->firstOrCreate([
                    //     'type' => 'lunch',
                    //     'start_time' => $lunchStartTime,
                    // ]);

                    //   Log::info('[AUTO LUNCH] Lunch STARTED', [
                    //     'employee_id' => $employee->id,
                    //     'trigger_time' => $lunchStartTime->toDateTimeString(),
                    // ]);



                    $payIncrement = (int) TimeTrackerHelper::getTimeTrackerSetting(
                        'pay_increments',
                        5
                    );

                    //  ROUND DOWN (IMPORTANT)
                    $roundedStart = TimeTrackerHelper::roundNearest($lunchStartTime, $payIncrement);

                    $entry->breaks()->firstOrCreate([
                        'type' => 'lunch',
                        'start_time' => $roundedStart,
                    ]);

                    Log::info('[AUTO LUNCH] Lunch STARTED (ROUNDED)', [
                        'employee_id' => $employee->id,
                        'original' => $lunchStartTime->toDateTimeString(),
                        'rounded' => $roundedStart->toDateTimeString(),
                    ]);


                    continue;
               
            }

            // =========================
            //  AUTO END LUNCH
            // =========================
            $activeBreak = $entry->activeBreak;

            if ($activeBreak && $activeBreak->type === 'lunch' && !$activeBreak->end_time) {

                // $start = Carbon::parse($activeBreak->start_time);
                // $end   = $start->copy()->addMinutes($lunchDuration);

                // if ($now->gte($end)) {

                //     $activeBreak->update([
                //         'end_time' => $end,
                //     ]);

                //     Log::info('[AUTO LUNCH] Lunch ENDED', [
                //         'employee_id' => $employee->id,
                //         'start' => $start->toDateTimeString(),
                //         'end' => $end->toDateTimeString(),
                //     ]);
                // }

                $start = Carbon::parse($activeBreak->start_time);

                $endRaw = $start->copy()->addMinutes($lunchDuration);

                $payIncrement = (int) TimeTrackerHelper::getTimeTrackerSetting(
                    'pay_increments',
                    5
                );

                //  ROUND END (IMPORTANT)
                $roundedEnd = TimeTrackerHelper::roundNearest($endRaw, $payIncrement);

                if ($now->gte($roundedEnd)) {

                    $activeBreak->update([
                        'end_time' => $roundedEnd,
                    ]);

                    Log::info('[AUTO LUNCH] Lunch ENDED (ROUNDED)', [
                        'employee_id' => $employee->id,
                        'start' => $start->toDateTimeString(),
                        'original_end' => $endRaw->toDateTimeString(),
                        'rounded_end' => $roundedEnd->toDateTimeString(),
                    ]);
                }
            }
        }

        Log::info('[AUTO LUNCH] Job finished');
    }
}