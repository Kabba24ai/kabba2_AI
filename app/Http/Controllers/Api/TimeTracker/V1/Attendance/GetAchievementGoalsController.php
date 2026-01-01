<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Attendance;


use App\Http\Controllers\Controller;

use App\Http\Resources\Api\TimeTracker\V1\TimeClock\AchievementGoalResource;

use App\Models\Iam\Personnel\AchievementGoal;

use App\Http\Controllers\Api\BaseController;

class GetAchievementGoalsController extends BaseController
{
    public function __invoke()
    {
        $goals = AchievementGoal::orderBy('display_order')
            ->get();

        return response()->json([
            'success' => true,
               'message' => 'Achievement Goal Geted successfully',
            'data' => AchievementGoalResource::collection($goals),
        ]);
    }
}

