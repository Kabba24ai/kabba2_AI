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
        $actualClockIn = now();

        // Get pay increment setting
     $payIncrement = TimeTrackerHelper::getTimeTrackerSetting('pay_increments', 30);


        //  Round clock-in UP
        $roundedClockIn = TimeTrackerHelper::roundUp(
            Carbon::parse($actualClockIn),
            (int) $payIncrement
        );

        //  Notes handling
        $notes = $request->validated('notes');

        if (empty($notes)) {
            $notes = sprintf(
                '%s clocked in at %s, rounded to %s',
                $user->first_name . ' ' . $user->last_name,
                $actualClockIn->format('H:i'),
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
