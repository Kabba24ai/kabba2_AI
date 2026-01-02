<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\VacationSummary;


use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class VacationSummaryController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $user = auth()->user();
        $year = Carbon::now()->year;

        //  Hours worked this year (from TimeEntry)
        $hoursWorked = $user->timeEntries()
            ->whereYear('clock_in', $year)
            ->whereNotNull('clock_out')
            ->sum('total_hours');

        //  Accrued hours
        // 80 hrs/year ÷ 2080 hrs = 0.03846 per hour
        $accruedHours = round($hoursWorked * (80 / 2080), 2);

        // //  Used vacation hours (approved only)
          $usedHours = $user
                        ->approvedVacationRequestsForYear($year)
                        ->with('requestHour')
                        ->get()
                        ->sum(fn ($req) => $req->requestHour?->hours ?? 0);


        //  Allotted hours (from user or config)
        $allottedHours = optional($user->vacationAllotmentHour)->hours ?? 0;

        return response()->json([
            'success' => true,
            'message' => 'User Vacation Summary fetched Successfully',
            'data' => [
                'allotted_hours' => $allottedHours,
                'accrued_hours' => min($accruedHours, $allottedHours), // cap
                'used_hours' => $usedHours,
                'hours_worked_this_year' => round($hoursWorked, 2),
                
                
            ],
        ]);
    }
}
