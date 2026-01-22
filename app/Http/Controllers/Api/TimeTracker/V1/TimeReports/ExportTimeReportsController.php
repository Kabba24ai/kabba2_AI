<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\TimeReports;

use App\Http\Controllers\Api\BaseController;
use App\Models\Iam\Personnel\User;
use App\Helpers\PayPeriodHelper;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class ExportTimeReportsController extends BaseController
{
    public function __invoke(Request $request): StreamedResponse
    {
        $periodNumber = (int) $request->get('pay_period', 1);
        [$startDate, $endDate] = PayPeriodHelper::getPeriodDates($periodNumber);

        $fileName = "time-reports-{$startDate->toDateString()}-{$endDate->toDateString()}.csv";

        $response = new StreamedResponse(function () use ($startDate, $endDate) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Employee Name',
                'Total Hours',
                'Paid Hours',
                'Lunch Hours',
                'Unpaid Hours',
                'Vacation Hours',
            ]);

            $users = User::with([
                'timeEntries' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('clock_in', [$startDate, $endDate])
                    ->whereNotNull('clock_out')
                    ->with('breaks');
                },
                'approvedVacationRequests.requestHour',
            ])
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'))
            ->active()
            ->orderBy('first_name')
            ->get();

            foreach ($users as $user) {
                $paidHours = round($user->timeEntries->sum('total_hours'), 2);

                $breakSeconds = 0;
                foreach ($user->timeEntries as $entry) {
                    foreach ($entry->breaks as $break) {
                        if ($break->start_time && $break->end_time) {
                            $breakSeconds +=
                                $break->start_time->diffInSeconds($break->end_time);
                        }
                    }
                }

                $lunchHours = round($breakSeconds / 3600, 2);
                $unpaidHours = $lunchHours;
                $totalHours = round($paidHours + $unpaidHours, 2);

                $vacationHours = round(
                    $user->approvedVacationRequests
                        ->where(fn ($req) =>
                            Carbon::parse($req->start_date)
                                ->between($startDate, $endDate)
                        )
                        ->sum(fn ($req) => $req->requestHour?->hours ?? 0),
                    2
                );

                fputcsv($handle, [
                    $user->full_name,
                    $totalHours,
                    $paidHours,
                    $lunchHours,
                    $unpaidHours,
                    $vacationHours,
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set(
            'Content-Disposition',
            "attachment; filename={$fileName}"
        );

        return $response;


        return $response->headers->set(
            'Content-Type',
            'text/csv'
        )->set(
            'Content-Disposition',
            "attachment; filename={$fileName}"
        );
    }
}
