<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Vacation;

use App\Http\Controllers\Api\BaseController;
use App\Models\Iam\Personnel\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ListVacationBalancesController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 30);
        $perPage = in_array($perPage, [5, 10, 30, 50, 100, 500]) ? $perPage : 10;

        $year = Carbon::now()->year;

        $users = User::with([
            'vacationAllotmentHour',
            'timeEntries' => function ($q) use ($year) {
                $q->whereYear('clock_in', $year)
                ->whereNotNull('clock_out');
            },
            'approvedVacationRequests.requestHour',
        ])
        ->where('vacation_eligible', true)
        ->active()
        ->orderBy('first_name')
        ->paginate($perPage);


           $data = $users->map(function ($user) use ($year) {

            $allottedHours = optional($user->vacationAllotmentHour)->hours ?? 0;

                //  Use model functions (single source of truth)
                $accruedHours = $user->getVacationAccruedHours($year);
                $usedHours = $user->getVacationUsedHours($year);
                $workedHours = $user->getEligibleWorkedHours($year);

                return [
                    'id' => (string) $user->id,
                    'employee_id' => (string) $user->id,
                    'employee_name' => trim($user->first_name . ' ' . $user->last_name),

                    'allotted_hours' => $allottedHours,
                    'accrued_hours' => min($accruedHours, $allottedHours),
                    'used_hours' => $usedHours,
                    'hours_worked_this_year' => $workedHours,

                    'vacation_allotment_hour_id' => $user->vacation_allotment_hour_id,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Vacation balances fetched successfully',
            'data' => $data,
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
        ]);
    }
}
