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

            // worked this year
            $hoursWorked = $user->timeEntries->sum('total_hours');

            // Accrued hours (80 hrs/year rule)
            $accruedHours = round($hoursWorked * (80 / 2080), 2);

            //  Allotted hours
            $allottedHours = optional($user->vacationAllotmentHour)->hours ?? 0;

            //  Cap accrued hours to allotment
            $accruedHours = min($accruedHours, $allottedHours);

            //  Used vacation hours (approved)

            $usedHours = $user
                        ->approvedVacationRequestsForYear($year)
                        ->with('requestHour')
                        ->get()
                        ->sum(fn ($req) => $req->requestHour?->hours ?? 0);

            $vacation_allotment_hour_id =  $user->vacation_allotment_hour_id ;

            return [
                'id' => (string) $user->id,
                'employee_id' => (string) $user->id,
                'employee_name' => trim($user->first_name . ' ' . $user->last_name),

                'allotted_hours' => $allottedHours,
                'accrued_hours' => $accruedHours,
                'used_hours' => $usedHours,

                'hours_worked_this_year' => round($hoursWorked, 2),

                'vacation_allotment_hour_id'=> $vacation_allotment_hour_id,
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
