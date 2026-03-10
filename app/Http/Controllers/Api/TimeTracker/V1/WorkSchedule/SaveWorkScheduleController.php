<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\WorkSchedule;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use App\Models\Iam\Personnel\WorkSchedule;
use App\Http\Requests\Api\TimeTracker\V1\WorkSchedule\SaveWorkScheduleRequest;

class SaveWorkScheduleController extends BaseController
{
    public function __invoke(SaveWorkScheduleRequest $request): JsonResponse
    {

        $schedules = $request->validated()['schedules'];

        foreach ($schedules as $schedule) {

            WorkSchedule::updateOrCreate(
                [
                    'user_id' => $schedule['employee_id'],
                    'date' => $schedule['date'],
                ],
                [
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time'],
                    'store_id' => $schedule['store_id'] ?? null,
                    'is_scheduled' => $schedule['is_scheduled'],
                    'hours' => $schedule['hours'],
                    'notes' => $schedule['notes'] ?? null,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Schedule saved successfully'
        ]);
    }
}