<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\TimeClock;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\TimeTracker\V1\TimeClock\ClockOutRequest;
use App\Http\Resources\Api\TimeTracker\V1\TimeClock\TimeEntryResource;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ClockOutController extends BaseController
{
    public function __invoke(ClockOutRequest $request): JsonResponse
    {
        $user = auth()->user();
        $entry = $user->activeTimeEntry;

        if (!$entry) {
            return response()->json([
                'success' => false,
                'message' => 'No active time entry found',
                'data' => null,
            ], 422);
        }

        
        $actualClockOut = now();
        $clockIn = Carbon::parse($entry->clock_in);

        //  Prevent clock-out before clock-in
        if ($actualClockOut->lessThan($clockIn)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot clock out before your clock-in time.',
                'data' => null,
            ], 422);
        }


        $entry->update([
            'clock_out' => now(),
            'break_duration' => $request->validated('break_duration') ?? 0,
            'status' => 'completed',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Clock-out successful',
            'data' => new TimeEntryResource($entry),
        ]);
    }
}
