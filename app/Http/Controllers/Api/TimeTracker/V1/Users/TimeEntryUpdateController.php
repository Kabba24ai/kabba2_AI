<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Users;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\TimeTracker\V1\Users\TimeEntryTimeUpdateRequest;
use App\Models\Iam\Personnel\TimeEntry;
use App\Models\Iam\Personnel\TimeEntryBreak;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Helpers\TimeTrackerHelper;
use Exception;

class TimeEntryUpdateController extends BaseController
{
    public function __invoke(TimeEntryTimeUpdateRequest $request)
    {
        try {

            $validated = $request->validated();

            // dd($validated);

            $entryId   = $validated['entry_id'];
            $breakId   = $validated['break_id'] ?? null;
            $type      = $validated['entry_type'];
            $newTime   = $validated['new_time'];

            $entry = TimeEntry::findOrFail($entryId);


            $baseDate = $entry->clock_in->toDateString();
            $newDateTime = Carbon::parse($baseDate . ' ' . $newTime);

            $payIncrement = (int) TimeTrackerHelper::getTimeTrackerSetting('pay_increments', 5);

            // rounded version (same rule you use in clock-in/out)
            $roundedDateTime = TimeTrackerHelper::roundNearest($newDateTime, $payIncrement);


            
            /*
            |--------------------------------------------------------------------------
            | CLOCK IN
            |--------------------------------------------------------------------------
            */
            if ($type === 'clock_in') {

                $entry->clock_in = $roundedDateTime;
                $entry->created_at = $newDateTime;

                $entry->save();

            }

            /*
            |--------------------------------------------------------------------------
            | CLOCK OUT
            |--------------------------------------------------------------------------
            */
            elseif ($type === 'clock_out') {

               $entry->clock_out = $roundedDateTime;
               $entry->status = 'completed';

                // $entry->timestamps = false; // disable auto timestamp
                $entry->updated_at = $newDateTime;

                $entry->save();

            }

            /*
            |--------------------------------------------------------------------------
            | BREAK UPDATE
            |--------------------------------------------------------------------------
            */
            elseif ($breakId) {

                $break = TimeEntryBreak::findOrFail($breakId);
                //  Get default lunch duration (minutes)
                        $lunchMinutes = TimeTrackerHelper::getTimeTrackerSetting(
                            'default_lunch_duration_minutes',
                            30 // fallback
                        );

                          $roundedBreakendTime   = $newDateTime->copy()->addMinutes((int) $lunchMinutes);

                if (in_array($type, ['lunch_out', 'unpaid_out'])) {
                    $break->start_time = $roundedBreakendTime;
                    $break->created_at = $newDateTime;
                }

                if (in_array($type, ['lunch_in', 'unpaid_in'])) {
                    $break->end_time = $roundedBreakendTime;
                    $break->updated_at = $newDateTime;
                }

                $break->save();

                // Recalculate totals
                $entry->refresh();
                $entry->save();

            }

            
            return response()->json([
                'success' => true,
                'message' => 'Time entry updated successfully',
            ]);

        } catch (Exception $e) {

            Log::error('TimeEntry Update Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Time update failed',
            ], 500);
        }
    }
}
