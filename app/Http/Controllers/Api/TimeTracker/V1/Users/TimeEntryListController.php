<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Users;

use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use App\Models\Iam\Personnel\TimeEntry;
use Illuminate\Http\Request;
use App\Helpers\PayPeriodHelper;
use Carbon\Carbon;

// class TimeEntryListController extends Controller
// {
//     public function __invoke(Request $request, User $user)
//     {
//         $entries = TimeEntry::with('breaks')
//             ->where('employee_id', $user->id)
//             ->orderBy('clock_in')
//             ->get();

//         $groupedByDay = $entries->groupBy(fn ($e) =>
//             $e->clock_in->toDateString()
//         );

//         $data = $groupedByDay->map(function ($dayEntries, $date) {

//             $entry = $dayEntries->first();

//             $dayData = [
//                 'date' => $date,

//                 'clock_in_actual' => $entry->created_at,
//                 'clock_in_adjusted' => $entry->clock_in,

//                 'lunch_start_actual' => null,
//                 'lunch_start_adjusted' => null,

//                 'lunch_end_actual' => null,
//                 'lunch_end_adjusted' => null,

//                 'unpaid_start_actual' => null,
//                 'unpaid_start_adjusted' => null,

//                 'unpaid_end_actual' => null,
//                 'unpaid_end_adjusted' => null,

//                 'clock_out_actual' => $entry->clock_out,
//                 'clock_out_adjusted' => $entry->clock_out,

//                 'total_hours' => 0,
//                 'total_unpaid_hours' => 0,
//                 'total_paid_hours' => 0,
//             ];

//             $unpaidSeconds = 0;

//             foreach ($entry->breaks as $break) {

//                 if ($break->type === 'lunch') {
//                     // Lunch IN
//                     $dayData['lunch_start_actual'] = $break->created_at;
//                     $dayData['lunch_start_adjusted'] = $break->start_time;

//                     // Lunch OUT
//                     if ($break->end_time) {
//                         $dayData['lunch_end_actual'] = $break->updated_at;
//                         $dayData['lunch_end_adjusted'] = $break->end_time;

//                         $unpaidSeconds +=
//                             $break->start_time->diffInSeconds($break->end_time);
//                     }
//                 }

//                 if ($break->type === 'other') {
//                     // Other IN
//                     $dayData['unpaid_start_actual'] = $break->created_at;
//                     $dayData['unpaid_start_adjusted'] = $break->start_time;

//                     // Other OUT
//                     if ($break->end_time) {
//                         $dayData['unpaid_end_actual'] = $break->updated_at;
//                         $dayData['unpaid_end_adjusted'] = $break->end_time;

//                         $unpaidSeconds +=
//                             $break->start_time->diffInSeconds($break->end_time);
//                     }
//                 }
//             }

//             // Totals (NO rounding logic applied here)
//             if ($entry->clock_in && $entry->clock_out) {
//                 $totalSeconds = $entry->clock_in->diffInSeconds($entry->clock_out);

//                 $dayData['total_hours'] = round($totalSeconds / 3600, 2);
//                 $dayData['total_unpaid_hours'] = round($unpaidSeconds / 3600, 2);
//                 $dayData['total_paid_hours'] = round(
//                     max($totalSeconds - $unpaidSeconds, 0) / 3600,
//                     2
//                 );
//             }

//             return $dayData;
//         })->values();

//         return response()->json([
//             'success' => true,
//             'data' => $data,
//              'message' => 'Employees Time Entry Get successfully.',
//         ]);
//     }

// }

class TimeEntryListController extends Controller
{
    public function __invoke(Request $request, User $user)
    {
         $periodNumber = (int) $request->get('pay_period', 1);

        [$startDate, $endDate] = PayPeriodHelper::getPeriodDates($periodNumber);

        $entries = TimeEntry::with('breaks')
            ->where('employee_id', $user->id)
            ->whereBetween('clock_in', [$startDate, $endDate])
            ->whereNotNull('clock_in')
            ->orderBy('clock_in')
            ->get()
            ->groupBy(fn ($e) => $e->clock_in->toDateString());

        $data = $entries->map(function ($dayEntries, $date) {

            $dayWorkedSeconds = 0;
            $dayUnpaidSeconds = 0;

            $entriesData = $dayEntries->map(function ($entry) use (&$dayWorkedSeconds, &$dayUnpaidSeconds) {

                $workedSeconds = 0;
                $unpaidSeconds = 0;

                if ($entry->clock_in && $entry->clock_out) {
                    $workedSeconds =
                        $entry->clock_in->diffInSeconds($entry->clock_out);
                }

                $lunchBreaks = [];
                $unpaidBreaks = [];

                foreach ($entry->breaks as $break) {

                    if (!$break->start_time || !$break->end_time) {
                        continue;
                    }

                    $seconds =
                        $break->start_time->diffInSeconds($break->end_time);

                    $breakData = [
                        'start' => [
                            'actual'   => $break->created_at,
                            'adjusted' => $break->start_time,
                        ],
                        'end' => [
                            'actual'   => $break->updated_at,
                            'adjusted' => $break->end_time,
                        ],
                        'seconds' => $seconds,
                    ];

                    if ($break->type === 'lunch') {
                        $lunchBreaks[] = $breakData;
                    }

                    if ($break->type === 'other') {
                        $unpaidBreaks[] = $breakData;
                    }

                    $unpaidSeconds += $seconds;
                }

                $paidSeconds = max($workedSeconds - $unpaidSeconds, 0);

                $dayWorkedSeconds += $workedSeconds;
                $dayUnpaidSeconds += $unpaidSeconds;

                return [
                    'entry_id' => $entry->id,

                    'clock_in' => [
                        'actual'   => $entry->created_at,
                        'adjusted' => $entry->clock_in,
                    ],

                    'clock_out' => $entry->clock_out ? [
                        'actual'   => $entry->clock_out,
                        'adjusted' => $entry->clock_out,
                    ] : null,

                    'lunch_breaks'  => $lunchBreaks,
                    'unpaid_breaks' => $unpaidBreaks,

                    'worked_seconds' => $workedSeconds,
                    'unpaid_seconds' => $unpaidSeconds,
                    'paid_seconds'   => $paidSeconds,
                ];
            });

            return [
                'date' => $date,
                'entries' => $entriesData,
                'totals' => [
                    'worked_hours' => round($dayWorkedSeconds / 3600, 2),
                    'unpaid_hours' => round($dayUnpaidSeconds / 3600, 2),
                    'paid_hours'   => round(
                        max($dayWorkedSeconds - $dayUnpaidSeconds, 0) / 3600,
                        2
                    ),
                ],
            ];
        })->values();

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
