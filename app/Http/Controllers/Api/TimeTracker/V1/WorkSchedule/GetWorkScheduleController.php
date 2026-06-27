<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\WorkSchedule;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;

use App\Models\Iam\Personnel\User;
use App\Models\Iam\Personnel\WorkSchedule;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;


class GetWorkScheduleController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $currentUser = Auth::user();

        $weekStart = $request->week_start;
        $employeeIds = $request->employee_ids ?? [];

        if (! $currentUser->isMasterAdmin()) {
            $storeEmployeeIds = User::active()
                ->where('store_id', $currentUser->store_id)
                ->pluck('id')
                ->all();

            $employeeIds = empty($employeeIds)
                ? $storeEmployeeIds
                : array_intersect($employeeIds, $storeEmployeeIds);
        }

        $days = (int) ($request->days ?? 7);

        $start = Carbon::parse($weekStart)->toDateString();

        // FIXED
        $end = Carbon::parse($weekStart)
            ->addDays($days - 1)
            ->toDateString();

        $schedules = WorkSchedule::with('store')
            ->whereIn('user_id', $employeeIds)
            ->whereBetween('date', [$start, $end])
            ->get()
            ->groupBy('user_id')
            ->map(function ($userSchedules) {
                return $userSchedules->groupBy('date');
            });

        return response()->json([
            'success' => true,
            'message' => 'WorkSchedule fetched successfully.',
            'data' => $schedules
        ]);
    }
}