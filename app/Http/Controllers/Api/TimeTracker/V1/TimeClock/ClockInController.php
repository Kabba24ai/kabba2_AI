<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\TimeClock;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\TimeTracker\V1\TimeClock\ClockInRequest;
use App\Http\Resources\Api\TimeTracker\V1\TimeClock\TimeEntryResource;
use App\Models\Iam\Personnel\TimeEntry;
use Carbon\Carbon;
use App\Helpers\TimeTrackerHelper;

use Illuminate\Http\JsonResponse;

class ClockInController extends BaseController
{
    public function __invoke(ClockInRequest $request): JsonResponse
    {
        $user = auth()->user();

        if ($user->activeTimeEntry) {
            return response()->json([
                'success' => false,
                'message' => 'Already clocked in',
                'data' => null,
            ], 422);
        }

        //  Actual clock-in time
        $actualClockIn = Carbon::now();
        $finalClockIn = $actualClockIn; 

        // GLOBAL + USER logic
        $globalLimitStart = TimeTrackerHelper::getTimeTrackerSetting('limit_start_time_to_shift', false);
        $userLimitStart = (bool) $user->limit_start_time;
        $shouldLimitStart = $globalLimitStart || $userLimitStart;


        // Check: user has store + limit_start_time enabled
         if ($user->store && $shouldLimitStart) {

            $dayName = $actualClockIn->format('l');

            $storeHours = $user->store->hoursOfOperation()
                ->where('day_name', $dayName)
                ->first();

            if ($storeHours && !$storeHours->is_closed) {

                $storeStartTime = Carbon::parse(
                    $actualClockIn->format('Y-m-d') . ' ' . $storeHours->start_time
                );

                if ($actualClockIn->lt($storeStartTime)) {
                    $finalClockIn = $storeStartTime;
                }
            }
        }

        // Get pay increment setting
        $payIncrement = TimeTrackerHelper::getTimeTrackerSetting('pay_increments', 5);

        //  Round clock-in UP
        $roundedClockIn = TimeTrackerHelper::roundNearest(
            Carbon::parse($finalClockIn),
            (int) $payIncrement
        );

        //  Notes handling
        $notes = $request->validated('notes');

        if (empty($notes)) {
            $notes = sprintf(
                '%s clocked in at %s, rounded to %s',
                $user->first_name . ' ' . $user->last_name,
                $finalClockIn->format('H:i'),
                $roundedClockIn->format('H:i')
            );
        }


        $entry = TimeEntry::create([
            'employee_id' => $user->id,
            'clock_in' =>  $roundedClockIn,
            'notes' => $notes,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Clock-in successful',
            'data' => new TimeEntryResource($entry),
        ]);
    }
}
