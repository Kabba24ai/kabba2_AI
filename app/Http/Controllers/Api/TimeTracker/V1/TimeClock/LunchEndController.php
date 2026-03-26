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

    // SETTINGS
    $minimumLunch = (int) TimeTrackerHelper::getTimeTrackerSetting(
        'minimum_lunch_duration_minutes',
        30
    );

    $payIncrement = (int) TimeTrackerHelper::getTimeTrackerSetting(
        'pay_increments',
        15
    );

    // TIMES
    $startTime = Carbon::parse($break->start_time);
    $actualEnd = Carbon::now();

    // ACTUAL DURATION
    $actualMinutes = $startTime->diffInMinutes($actualEnd);

    // FINAL END TIME
    if ($actualMinutes < $minimumLunch) {

        // FORCE MINIMUM
        $finalEnd = $startTime->copy()->addMinutes($minimumLunch);

    } else {

        // APPLY PAY INCREMENT ROUNDING (ROUND UP)
        $finalEnd = TimeTrackerHelper::roundUp(
            $actualEnd,
            $payIncrement
        );
    }

    // SAVE
    $break->update([
        'end_time' => $finalEnd,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Lunch ended',
        'data' => [
            'start_time' => $startTime->toDateTimeString(),
            'actual_end' => $actualEnd->toDateTimeString(),
            'final_end'  => $finalEnd->toDateTimeString(),
            'end_time'   => $finalEnd->toDateTimeString(),
        ],
    ]);
}

    // public function __invoke(): JsonResponse
    // {
    //     $entry = auth()->user()->activeTimeEntry;
    //     $break = $entry?->activeBreak;

    //     if (!$break || $break->type !== 'lunch') {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'No active lunch break',
    //         ], 422);
    //     }

    //     //  Get default lunch duration (minutes)
    //     $lunchMinutes = TimeTrackerHelper::getTimeTrackerSetting(
    //         'default_lunch_duration_minutes',
    //         30 // fallback
    //     );

    //     //  Calculate end time
    //     $startTime = Carbon::parse($break->start_time);
    //     $endTime   = $startTime->copy()->addMinutes((int) $lunchMinutes);

    //     //  Update break
    //     $break->update([
    //         'end_time' => $endTime,
    //     ]);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Lunch ended',
    //         'data' => [
    //             'start_time' => $startTime->toDateTimeString(),
    //             'end_time'   => $endTime->toDateTimeString(),
    //             'duration'   => $lunchMinutes . ' minutes',
    //         ],
    //     ]);
    // }
}
