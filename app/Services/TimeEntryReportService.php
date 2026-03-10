<?php

namespace App\Services;

use App\Models\Iam\Personnel\TimeEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TimeEntryReportService
{

private function secondsToHoursMinutes($seconds)
{
    return number_format($seconds / 3600, 2);
}

    public function build(Collection $entries): Collection
    {
        return $entries->groupBy(fn ($e) => $e->clock_in->toDateString())
            ->map(function ($dayEntries, $date) {
            $dayWorkedSeconds = 0;
            $dayUnpaidSeconds = 0;
            $dayPaidSeconds = 0;
            $entriesData = $dayEntries->map(function ($entry) use (&$dayWorkedSeconds, &$dayUnpaidSeconds, &$dayPaidSeconds) {

                $workedSeconds = 0;
                $unpaidSeconds = 0;

              if ($entry->clock_in && $entry->clock_out) {
$workedSeconds = $entry->clock_in->diffInSeconds($entry->clock_out);
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
                           'id' => $break->id,
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
                $dayPaidSeconds   += $paidSeconds;
                return [
                    'entry_id' => $entry->id,

                    'clock_in' => [
                        'actual'   => $entry->created_at,
                        'adjusted' => $entry->clock_in,
                    ],

                    'clock_out' => $entry->clock_out ? [
                        'actual'   => $entry->updated_at,
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
                    'worked_hours' => $this->secondsToHoursMinutes($dayWorkedSeconds),
'unpaid_hours' => $this->secondsToHoursMinutes($dayUnpaidSeconds),
'paid_hours' => $this->secondsToHoursMinutes($dayPaidSeconds),
                ],
            ];
        })->values();
    }
}
