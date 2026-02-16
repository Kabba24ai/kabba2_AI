<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Users;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\TimeTracker\V1\Users\TimeEntryTimeUpdateRequest;
use App\Models\Iam\Personnel\TimeEntry;
use App\Models\Iam\Personnel\TimeEntryBreak;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

class TimeEntryUpdateController extends BaseController
{
    public function __invoke(TimeEntryTimeUpdateRequest $request)
    {
        try {

            $validated = $request->validated();

            // Log::info(' TimeEntry Update Request Received', [
            //     'payload' => $request->all(),
            //     'validated' => $validated,
            // ]);

            $entryId   = $validated['entry_id'];
            $breakId   = $validated['break_id'] ?? null;
            $type      = $validated['entry_type'];
            $newTime   = $validated['new_time'];

            $entry = TimeEntry::findOrFail($entryId);

            // Log::info('Before Update', [
            //     'entry_id' => $entry->id,
            //     'clock_in' => $entry->clock_in,
            //     'clock_out' => $entry->clock_out,
            //     'total_hours' => $entry->total_hours,
            // ]);

            $baseDate = $entry->clock_in->toDateString();
            $newDateTime = Carbon::parse($baseDate . ' ' . $newTime);

            /*
            |--------------------------------------------------------------------------
            | CLOCK IN
            |--------------------------------------------------------------------------
            */
            if ($type === 'clock_in') {

                $entry->clock_in = $newDateTime;
                $entry->created_at = $newDateTime;

                $entry->save();

                // Log::info('Clock In Updated', [
                //     'new_clock_in' => $entry->clock_in,
                // ]);
            }

            /*
            |--------------------------------------------------------------------------
            | CLOCK OUT
            |--------------------------------------------------------------------------
            */
            elseif ($type === 'clock_out') {

               $entry->clock_out = $finalClockOutTime;
                $entry->status = 'completed';

                $entry->timestamps = false; // disable auto timestamp
                $entry->updated_at = $finalClockOutTime;

                $entry->save();


                // Log::info(' Clock Out Updated', [
                //     'new_clock_out' => $entry->clock_out,
                // ]);
            }

            /*
            |--------------------------------------------------------------------------
            | BREAK UPDATE
            |--------------------------------------------------------------------------
            */
            elseif ($breakId) {

                $break = TimeEntryBreak::findOrFail($breakId);

                // Log::info(' Before Break Update', [
                //     'break_id' => $break->id,
                //     'start_time' => $break->start_time,
                //     'end_time' => $break->end_time,
                // ]);

                if (in_array($type, ['lunch_out', 'unpaid_out'])) {
                    $break->start_time = $newDateTime;
                    $break->created_at = $newDateTime;
                }

                if (in_array($type, ['lunch_in', 'unpaid_in'])) {
                    $break->end_time = $newDateTime;
                    $break->updated_at = $newDateTime;
                }

                $break->save();

                // Recalculate totals
                $entry->refresh();
                $entry->save();

                // Log::info(' Break Updated', [
                //     'break_id' => $break->id,
                //     'updated_start' => $break->start_time,
                //     'updated_end' => $break->end_time,
                // ]);
            }

            // Log::info(' After Update', [
            //     'entry_id' => $entry->id,
            //     'clock_in' => $entry->clock_in,
            //     'clock_out' => $entry->clock_out,
            //     'total_hours' => $entry->fresh()->total_hours,
            // ]);

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
