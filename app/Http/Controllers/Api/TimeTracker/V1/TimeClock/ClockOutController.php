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
    $entry = auth()->user()->activeTimeEntry;

    if (!$entry) {
        return response()->json([
            'success' => false,
            'message' => 'No active time entry found',
        ], 422);
    }

    $now = Carbon::now();
    $clockIn = Carbon::parse($entry->clock_in);

    //  If clock-out is before clock-in, force it to clock-in
    if ($now->lessThan($clockIn)) {
        $now = $clockIn;
    }

    //  Close active break automatically
    if ($entry->activeBreak) {
        $entry->activeBreak->update([
            'end_time' => $now,
        ]);
    }

    //  Single update (saving() runs ONCE)
    $entry->update([
        'clock_out' => $now,
        'status' => 'completed',
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Clock-out successful',
        'data' => new TimeEntryResource($entry->fresh()),
    ]);
}

}
