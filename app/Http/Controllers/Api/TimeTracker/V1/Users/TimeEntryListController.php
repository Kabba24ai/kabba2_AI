<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Users;

use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use App\Models\Iam\Personnel\TimeEntry;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TimeEntryListController extends Controller
{
    public function __invoke(Request $request, User $user)
    {
        $entries = TimeEntry::with('breaks')
            ->where('employee_id', $user->id)
            ->orderBy('clock_in')
            ->get();

        $groupedByDay = $entries->groupBy(fn ($e) =>
            $e->clock_in->toDateString()
        );

        $data = $groupedByDay->map(function ($dayEntries, $date) {

            $entry = $dayEntries->first();

            $dayData = [
                'date' => $date,

                'clock_in_actual' => $entry->created_at?->toTimeString(),
                'clock_in_adjusted' => $entry->clock_in?->toTimeString(),

                'lunch_start_actual' => null,
                'lunch_start_adjusted' => null,

                'lunch_end_actual' => null,
                'lunch_end_adjusted' => null,

                'unpaid_start_actual' => null,
                'unpaid_start_adjusted' => null,

                'unpaid_end_actual' => null,
                'unpaid_end_adjusted' => null,

                'clock_out_actual' => $entry->clock_out?->toTimeString(),
                'clock_out_adjusted' => $entry->clock_out?->toTimeString(),

                'total_hours' => 0,
                'total_unpaid_hours' => 0,
                'total_paid_hours' => 0,
            ];

            $unpaidSeconds = 0;

            foreach ($entry->breaks as $break) {

                if ($break->type === 'lunch') {
                    // Lunch IN
                    $dayData['lunch_start_actual'] = $break->created_at?->toTimeString();
                    $dayData['lunch_start_adjusted'] = $break->start_time?->toTimeString();

                    // Lunch OUT
                    if ($break->end_time) {
                        $dayData['lunch_end_actual'] = $break->updated_at?->toTimeString();
                        $dayData['lunch_end_adjusted'] = $break->end_time?->toTimeString();

                        $unpaidSeconds +=
                            $break->start_time->diffInSeconds($break->end_time);
                    }
                }

                if ($break->type === 'other') {
                    // Other IN
                    $dayData['unpaid_start_actual'] = $break->created_at?->toTimeString();
                    $dayData['unpaid_start_adjusted'] = $break->start_time?->toTimeString();

                    // Other OUT
                    if ($break->end_time) {
                        $dayData['unpaid_end_actual'] = $break->updated_at?->toTimeString();
                        $dayData['unpaid_end_adjusted'] = $break->end_time?->toTimeString();

                        $unpaidSeconds +=
                            $break->start_time->diffInSeconds($break->end_time);
                    }
                }
            }

            // Totals (NO rounding logic applied here)
            if ($entry->clock_in && $entry->clock_out) {
                $totalSeconds = $entry->clock_in->diffInSeconds($entry->clock_out);

                $dayData['total_hours'] = round($totalSeconds / 3600, 2);
                $dayData['total_unpaid_hours'] = round($unpaidSeconds / 3600, 2);
                $dayData['total_paid_hours'] = round(
                    max($totalSeconds - $unpaidSeconds, 0) / 3600,
                    2
                );
            }

            return $dayData;
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

}
