<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Users;

use App\Http\Controllers\Api\BaseController;
use App\Models\Iam\Personnel\User;
use App\Models\Iam\Personnel\TimeEntry;
use App\Helpers\PayPeriodHelper;
use App\Services\TimeEntryReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ExportEmployeeTimeEntriesController extends BaseController
{
    public function __invoke(Request $request, User $user): StreamedResponse
    {
        $periodNumber = (int) $request->get('pay_period', 1);
        [$startDate, $endDate] = PayPeriodHelper::getPeriodDates($periodNumber);

        $fileName = "time-entries-{$user->full_name}-{$startDate->toDateString()}-{$endDate->toDateString()}.csv";

        return new StreamedResponse(function () use (
            $user,
            $startDate,
            $endDate
        ) {

            $handle = fopen('php://output', 'w');

            // =========================
            // CSV HEADER
            // =========================
            fputcsv($handle, [
                'Date',
                'Row Type',
                'Entry #',
                'Clock In',
                'Clock Out',
                'Break Type',
                'Break Start',
                'Break End',
                'Worked Hours',
                'Unpaid Hours',
                'Paid Hours',
            ]);

            // =========================
            // FETCH RAW ENTRIES
            // =========================
            $entries = TimeEntry::with('breaks')
                ->where('employee_id', $user->id)
                ->whereBetween('clock_in', [$startDate, $endDate])
                ->orderBy('clock_in')
                ->get();

            // =========================
            // BUILD REPORT (SINGLE SOURCE OF TRUTH)
            // =========================
            $report = app(TimeEntryReportService::class)->build($entries);

            // =========================
            // DEBUG JSON (OPTIONAL)
            // =========================
            $debugJson = [
                'employee' => [
                    'id' => $user->id,
                    'name' => $user->full_name,
                ],
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end'   => $endDate->toDateString(),
                ],
                'days' => $report,
            ];

            // =========================
            // CSV OUTPUT
            // =========================
            foreach ($report as $day) {

                foreach ($day['entries'] as $index => $entry) {

                    // CLOCK IN (actual first, adjusted in brackets)
                    $clockIn = Carbon::parse($entry['clock_in']['actual'])->format('g:i A');

                    if (
                        Carbon::parse($entry['clock_in']['actual'])
                            ->ne(Carbon::parse($entry['clock_in']['adjusted']))
                    ) {
                        $clockIn .= ' (' .
                            Carbon::parse($entry['clock_in']['adjusted'])->format('g:i A')
                            . ')';
                    }

                    // CLOCK OUT
                    $clockOut = '';
                    if ($entry['clock_out']) {
                        $clockOut = Carbon::parse($entry['clock_out']['actual'])->format('g:i A');
                    }

                    // ENTRY ROW
                    fputcsv($handle, [
                        $day['date'],
                        'ENTRY',
                        $index + 1,
                        $clockIn,
                        $clockOut,
                        '',
                        '',
                        '',
                        round($entry['worked_seconds'] / 3600, 2),
                        round($entry['unpaid_seconds'] / 3600, 2),
                        round($entry['paid_seconds'] / 3600, 2),
                    ]);

                    // BREAK ROWS
                    foreach ($entry['lunch_breaks'] as $break) {
                        fputcsv($handle, [
                            $day['date'],
                            'BREAK',
                            $index + 1,
                            '',
                            '',
                            'Lunch',
                            Carbon::parse($break['start']['actual'])->format('g:i A')
                                . ' (' . Carbon::parse($break['start']['adjusted'])->format('g:i A') . ')',
                            Carbon::parse($break['end']['actual'])->format('g:i A')
                                . ' (' . Carbon::parse($break['end']['adjusted'])->format('g:i A') . ')',
                            '',
                            round($break['seconds'] / 3600, 2),
                            '',
                        ]);
                    }

                    foreach ($entry['unpaid_breaks'] as $break) {
                        fputcsv($handle, [
                            $day['date'],
                            'BREAK',
                            $index + 1,
                            '',
                            '',
                            'Other',
                            Carbon::parse($break['start']['actual'])->format('g:i A')
                                . ' (' . Carbon::parse($break['start']['adjusted'])->format('g:i A') . ')',
                            Carbon::parse($break['end']['actual'])->format('g:i A')
                                . ' (' . Carbon::parse($break['end']['adjusted'])->format('g:i A') . ')',
                            '',
                            round($break['seconds'] / 3600, 2),
                            '',
                        ]);
                    }
                }

                // DAY TOTAL ROW
                fputcsv($handle, [
                    $day['date'],
                    'DAY TOTAL',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    $day['totals']['worked_hours'],
                    $day['totals']['unpaid_hours'],
                    $day['totals']['paid_hours'],
                ]);
            }

            fclose($handle);

            // =========================
            // SAFE DEBUG LOGGING
            // =========================
            Log::info('ExportEmployeeTimeEntries CSV Debug', [
                'data' => $debugJson,
            ]);

        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}
