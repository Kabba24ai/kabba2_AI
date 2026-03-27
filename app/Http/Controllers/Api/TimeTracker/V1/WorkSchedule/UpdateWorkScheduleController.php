<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\WorkSchedule;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;
use Carbon\Carbon;

use App\Models\Iam\Personnel\WorkSchedule;
use App\Http\Requests\Api\TimeTracker\V1\WorkSchedule\UpdateWorkScheduleRequest;

class UpdateWorkScheduleController extends BaseController
{
    public function __invoke(UpdateWorkScheduleRequest $request): JsonResponse
{
    try {

        $data = $request->validated();

       

        // Normalize time
        $startTime = !empty($data['start_time'])
            ? Carbon::parse($data['start_time'])->format('H:i:s')
            : null;

        $endTime = !empty($data['end_time'])
            ? Carbon::parse($data['end_time'])->format('H:i:s')
            : null;

       

        $record = WorkSchedule::updateOrCreate(
            [
                'user_id' => $data['employee_id'],
                'date' => $data['date'],
            ],
            [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'store_id' => $data['store_id'] ?? null,
                'is_scheduled' => $data['is_scheduled'],
                'hours' => $data['hours'],
                'notes' => $data['notes'] ?? null,
            ]
        );


        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully',
            'data' => $record
        ]);

    } catch (Throwable $e) {

        //  Log error properly
        Log::error('WorkSchedule Update Failed', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to update schedule'
        ], 500);
    }
}
}