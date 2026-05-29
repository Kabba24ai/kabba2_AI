<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Users;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\TimeTracker\V1\Users\TimeEntryDeleteRequest;
use App\Models\Iam\Personnel\TimeEntry;
use App\Models\Iam\Personnel\TimeEntryBreak;
use Illuminate\Support\Facades\Log;
use Exception;

class TimeEntryDeleteController extends BaseController
{
    public function __invoke(TimeEntryDeleteRequest $request)
    {
        try {

            $validated = $request->validated();

            $entryId = $validated['entry_id'];
            $breakId = $validated['break_id'] ?? null;
            $type    = $validated['entry_type'];

            $entry = TimeEntry::findOrFail($entryId);

            /*
            |--------------------------------------------------------------------------
            | CLOCK IN DELETE
            |--------------------------------------------------------------------------
            |
            | Delete FULL entry + ALL breaks
            |
            */

            if ($type === 'clock_in') {

                // delete all breaks
                $entry->breaks()->delete();

                // delete entry
                $entry->delete();

            }

            /*
            |--------------------------------------------------------------------------
            | CLOCK OUT DELETE
            |--------------------------------------------------------------------------
            |
            | Re-open active entry
            |
            */

            elseif ($type === 'clock_out') {

                $entry->clock_out = null;

                $entry->status = 'active';

                $entry->save();
            }

            /*
            |--------------------------------------------------------------------------
            | BREAK DELETE
            |--------------------------------------------------------------------------
            */

            elseif ($breakId) {

                $break = TimeEntryBreak::findOrFail($breakId);

                /*
                |--------------------------------------------------------------------------
                | BREAK START DELETE
                |--------------------------------------------------------------------------
                |
                | Delete FULL break
                |
                */

                if (in_array($type, ['lunch_in', 'unpaid_in'])) {

                    $break->delete();
                }

                /*
                |--------------------------------------------------------------------------
                | BREAK END DELETE
                |--------------------------------------------------------------------------
                |
                | Re-open break
                |
                */

                elseif (in_array($type, ['lunch_in', 'unpaid_in'])) {

                    $break->end_time = null;

                    $break->save();
                }

                // refresh totals
                $entry->refresh();
                $entry->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Time entry deleted successfully',
            ]);

        } catch (Exception $e) {

            Log::error('TimeEntry Delete Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Delete failed',
            ], 500);
        }
    }
}