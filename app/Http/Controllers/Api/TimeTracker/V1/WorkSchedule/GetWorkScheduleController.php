<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\WorkSchedule;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;

use App\Models\Iam\Personnel\WorkSchedule;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class GetWorkScheduleController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $weekStart = $request->week_start;
        $employeeIds = $request->employee_ids ?? [];

        $start = Carbon::parse($weekStart);
        $end = (clone $start)->addDays(6);

        $schedules = WorkSchedule::whereIn('user_id', $employeeIds)
            ->whereBetween('date', [$start, $end])
            ->get()
            ->groupBy(['user_id', 'date']);

        return response()->json([
            'success' => true,
            'message' => 'WorkSchedule fetched successfully.',
            'data' => $schedules
        ]);
    }
}