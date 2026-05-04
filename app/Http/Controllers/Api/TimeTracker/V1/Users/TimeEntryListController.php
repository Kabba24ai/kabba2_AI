<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Users;

use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use App\Models\Iam\Personnel\TimeEntry;
use Illuminate\Http\Request;
use App\Helpers\PayPeriodHelper;
use App\Services\TimeEntryReportService;


use Carbon\Carbon;

class TimeEntryListController extends Controller
{
    public function __invoke(Request $request, User $user)
    {
        $periodNumber = (int) $request->get('pay_period', 1);

        [$startDate, $endDate] = PayPeriodHelper::getPeriodDates($periodNumber);

        $today = Carbon::today();

        if ($endDate->gt($today)) {
            $endDate = $today;
        }

        $entries = TimeEntry::with('breaks')
            ->where('employee_id', $user->id)
            // ->whereBetween('clock_in', [$startDate, $endDate])
            ->whereBetween('clock_in', [
            $startDate->copy()->startOfDay(),
            $endDate->copy()->endOfDay(),
            ])
            ->whereNotNull('clock_in')
            ->orderBy('clock_in')
            ->get();

        // $data = app(TimeEntryReportService::class)->build($entries);
        $data = app(TimeEntryReportService::class)->build($entries, $startDate, $endDate);


        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'startDate' => $startDate->toDateString(),
                'endDate'   => $endDate->toDateString(),
            ],
            'message' => 'Employee daily time entries fetched successfully',
        ]);
    }
}
