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

             // 🔍 DEBUG OVERRIDE VALUE
            Log::info('[AUTO LUNCH CHECK]', [
                'employee_id' => $employee->id,
                'lunch_override_raw' => $employee->lunch_override,
                'type' => gettype($employee->lunch_override),
                'casted_int' => (int) $employee->lunch_override,
            ]);


               // ✅ STRICT OVERRIDE CHECK
            if ((int) $employee->lunch_override === 1) {

                Log::warning('🚫 [AUTO LUNCH BLOCKED - OVERRIDE ENABLED]', [
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

            if (!$store->lunch_start_time) {
                Log::info('[AUTO LUNCH] Skipped - no lunch_start_time', [
                    'employee_id' => $employee->id,
                ]);
                continue;
            }

            $today = $now->toDateString();

            $lunchStartTime = Carbon::parse(
                $today . ' ' . $store->lunch_start_time
            );

            // Already had lunch
            $alreadyHadLunch = $entry->breaks()
            ->where('type', 'lunch')
            ->whereDate('start_time', $today)
            ->exists();

            // START
            if (!$alreadyHadLunch && !$entry->activeBreak) {

                if ($now->between($lunchStartTime, $lunchStartTime->copy()->addHours(2))) {

                    $entry->breaks()->firstOrCreate([
                        'type' => 'lunch',
                        'start_time' => $lunchStartTime,
                    ]);

                    Log::info('[AUTO LUNCH] Lunch STARTED', [
                        'employee_id' => $employee->id,
                    ]);

                    continue;
                }
            }

            // =========================
            //  AUTO END LUNCH
            // =========================
            $activeBreak = $entry->activeBreak;

            if ($activeBreak && $activeBreak->type === 'lunch' && !$activeBreak->end_time) {

                $start = Carbon::parse($activeBreak->start_time);
                $end   = $start->copy()->addMinutes($lunchDuration);

                if ($now->gte($end)) {

                    $activeBreak->update([
                        'end_time' => $end,
                    ]);

                    Log::info('[AUTO LUNCH] Lunch ENDED', [
                        'employee_id' => $employee->id,
                        'start' => $start->toDateTimeString(),
                        'end' => $end->toDateTimeString(),
                    ]);
                }
            }
        }

        Log::info('[AUTO LUNCH] Job finished');
    }
}