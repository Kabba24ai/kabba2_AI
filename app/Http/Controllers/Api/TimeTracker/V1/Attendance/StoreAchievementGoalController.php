<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Attendance;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\Api\TimeTracker\V1\TimeClock\AchievementGoalResource;

use App\Http\Requests\Api\TimeTracker\V1\Attendance\StoreGoalRequest;

use App\Models\Iam\Personnel\AchievementGoal;
use Illuminate\Http\Request;

class StoreAchievementGoalController extends BaseController
{
    public function __invoke(StoreGoalRequest $request)
    {
        $data = $request->validated();

        $goal = AchievementGoal::create($data);

        return response()->json([
            'success' => true,
            'message' => 'New Achievement Goal Added Successfully',
            'data'    => new AchievementGoalResource($goal),
        ]);
    }
}
