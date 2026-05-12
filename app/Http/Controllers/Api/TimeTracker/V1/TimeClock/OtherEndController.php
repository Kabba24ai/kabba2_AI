<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\TimeClock;

use App\Http\Controllers\Api\BaseController;
use App\Helpers\TimeTrackerHelper;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class OtherEndController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        $entry = auth()->user()->activeTimeEntry;
        $break = $entry?->activeBreak;

        if (!$break || $break->type !== 'other') {
            return response()->json([
                'message' => 'No active other break'
            ], 422);
        }

        //  Get default lunch duration (minutes)
        $lunchMinutes = TimeTrackerHelper::getTimeTrackerSetting(
            'default_lunch_duration_minutes',
            30 
        );

         //  Calculate end time
        $startTime = Carbon::parse($break->start_time);
        $endTime   = $startTime->copy()->addMinutes((int) $lunchMinutes);

        $break->update([
            'end_time' => $endTime,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Other break ended'
        ]);
    }
}
