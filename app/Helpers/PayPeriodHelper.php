<?php

namespace App\Helpers;

use Carbon\Carbon;

class PayPeriodHelper
{
    private static function carbon($value): Carbon
    {
        return $value instanceof Carbon
            ? $value
            : Carbon::parse($value);
    }

    public static function getPeriodDates(int $periodNumber): array
    {
        $type = TimeTrackerHelper::getTimeTrackerSetting(
            'pay_period_type',
            'biweekly'
        );

        $startDateSetting = TimeTrackerHelper::getTimeTrackerSetting(
            'pay_period_start_date',
            '2026-01-01'
        );

        $periodLength = $type === 'weekly' ? 7 : 14;

        $start = self::carbon($startDateSetting)
            ->copy()
            ->addDays(($periodNumber - 1) * $periodLength)
            ->startOfDay();

        $end = $start
            ->copy()
            ->addDays($periodLength - 1)
            ->endOfDay();

        return [$start, $end];
    }

    public static function listPeriods(int $count = 12): array
    {
        $type = TimeTrackerHelper::getTimeTrackerSetting(
            'pay_period_type',
            'biweekly'
        );

        $startDateSetting = TimeTrackerHelper::getTimeTrackerSetting(
            'pay_period_start_date',
            '2026-01-01'
        );

        $length = $type === 'weekly' ? 7 : 14;

        $periods = [];
        $currentStart = self::carbon($startDateSetting);

        for ($i = 1; $i <= $count; $i++) {
            $end = $currentStart->copy()->addDays($length - 1);

            $periods[] = [
                'number'     => $i,
                'start_date' => $currentStart->toDateString(),
                'end_date'   => $end->toDateString(),
                'label'      => "Period {$i}: {$currentStart->format('n/j/Y')} - {$end->format('n/j/Y')}",
            ];

            $currentStart = $end->addDay();
        }

        return $periods;
    }
}
