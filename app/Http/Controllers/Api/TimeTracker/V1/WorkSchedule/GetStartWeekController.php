<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\WorkSchedule;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use App\Helpers\TimeTrackerHelper;

class GetStartWeekController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        $startDate = TimeTrackerHelper::getTimeTrackerSetting(
            'pay_period_start_date',
            '2026-01-01'
        );

        return response()->json([
            'success' => true,
            'message' => 'Start week fetched successfully',
            'data' => [
                'start_date' => $startDate,
            ],
        ]);
    }
}