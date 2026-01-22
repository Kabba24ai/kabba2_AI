<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\TimeClock;

use App\Http\Controllers\Api\BaseController;
use App\Helpers\TimeTrackerHelper;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class LunchEndController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        $entry = auth()->user()->activeTimeEntry;
        $break = $entry?->activeBreak;

        if (!$break || $break->type !== 'lunch') {
            return response()->json([
                'success' => false,
                'message' => 'No active lunch break',
            ], 422);
        }

        //  Get default lunch duration (minutes)
        $lunchMinutes = TimeTrackerHelper::getTimeTrackerSetting(
            'default_lunch_duration_minutes',
            30 // fallback
        );

        //  Calculate end time
        $startTime = Carbon::parse($break->start_time);
        $endTime   = $startTime->copy()->addMinutes((int) $lunchMinutes);

        //  Update break
        $break->update([
            'end_time' => $endTime,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lunch ended',
            'data' => [
                'start_time' => $startTime->toDateTimeString(),
                'end_time'   => $endTime->toDateTimeString(),
                'duration'   => $lunchMinutes . ' minutes',
            ],
        ]);
    }
}
