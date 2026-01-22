<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Attendance;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\Api\TimeTracker\V1\TimeClock\AchievementGoalResource;
use App\Http\Requests\Api\TimeTracker\V1\Attendance\UpdateGoalRequest;

use App\Models\Iam\Personnel\AchievementGoal;
use Illuminate\Http\Request;

class UpdateAchievementGoalController extends BaseController
{
    public function __invoke(UpdateGoalRequest $request,$goal)
    {
        $goaldata = AchievementGoal::find($goal);  

        $data = $request->validated();

        $goaldata->update($data);

        return response()->json([
            'success' => true,
            'message' => 'New Achievement Goal Updated Successfully',
            'data'    => new AchievementGoalResource($goaldata),
        ]);
    }
}
