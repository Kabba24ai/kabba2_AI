<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\VacationSummary;


use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class VacationSummaryController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $user = auth()->user();
        $year = Carbon::now()->year;

        $accruedHours = $user->getVacationAccruedHours($year);
        $usedHours = $user->getVacationUsedHours($year);
        $workedHours = $user->getEligibleWorkedHours($year);

        $allottedHours = optional($user->vacationAllotmentHour)->hours ?? 0;

        return response()->json([
            'success' => true,
            'message' => 'User Vacation Summary fetched Successfully',
            'data' => [
                'allotted_hours' => $allottedHours,
                'accrued_hours' => min($accruedHours, $allottedHours), // cap
                'used_hours' => $usedHours,
                'hours_worked_this_year' => round($workedHours, 2),
            ],
        ]);
    }
}
