<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Users;

use App\Http\Controllers\Api\BaseController;
use App\Models\Iam\Personnel\TimeEntry;
use App\Models\Iam\Personnel\User;
use App\Http\Requests\Api\TimeTracker\V1\Users\CreateEmptyTimeEntryRequest;
use Carbon\Carbon;
use App\Helpers\TimeTrackerHelper;

class CreateEmptyTimeEntryController extends BaseController
{
    public function __invoke(CreateEmptyTimeEntryRequest $request)
    {
        $validated = $request->validated();

        $userId = $validated['user_id'];
        $date   = $validated['date'];
        $time   = $validated['time'];
        $type   = $validated['entry_type'];

        $user = User::findOrFail($userId);

        $entry = TimeEntry::where('employee_id', $user->id)
            ->whereDate('clock_in', $date)
            ->first();

        $newDateTime = Carbon::parse($date . ' ' . $time);

        $payIncrement = (int) TimeTrackerHelper::getTimeTrackerSetting('pay_increments', 5);

        $roundedDateTime = TimeTrackerHelper::roundNearest($newDateTime, $payIncrement);

        /*
        |--------------------------------------------------------------------------
        | CLOCK IN
        |--------------------------------------------------------------------------
        */
        if ($type === 'clock_in') {

            if (!$entry) {

                $entry = new TimeEntry();

                $entry->employee_id = $user->id;
                $entry->clock_in    = $roundedDateTime;
                $entry->created_at  = $newDateTime;
                $entry->status      = 'active';

                //  SAME AS BREAK LOGIC
                $entry->timestamps = false;

                $entry->save();

            } else {

                $entry->clock_in   = $roundedDateTime;
                $entry->created_at = $newDateTime;
                $entry->status     = 'active';

                // disable auto override
                $entry->timestamps = false;

                $entry->save();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CLOCK OUT
        |--------------------------------------------------------------------------
        */
        if ($type === 'clock_out') {

            if (!$entry) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please add clock-in first',
                ], 422);
            }

            $clockIn = $entry->clock_in;

            if ($newDateTime->lt($clockIn)) {
                $newDateTime = $clockIn;
            }

            $roundedDateTime = TimeTrackerHelper::roundNearest($newDateTime, $payIncrement);

            $entry->clock_out = $roundedDateTime;
            $entry->updated_at = $newDateTime;
            $entry->status = 'completed';

            //  SAME PATTERN
            $entry->timestamps = false;

            $entry->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Time entry saved successfully',
            'data'    => $entry->fresh(),
        ]);
    }
}