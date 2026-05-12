<?php

namespace App\Modules\SchedulingAssistant\Support;

use Carbon\Carbon;

class DateRangeHelper
{
    public static function combine(?string $date, ?string $time): ?Carbon
    {
        if (empty($date)) {
            return null;
        }

        $time = $time ?: '00:00:00';

        return Carbon::parse(trim($date . ' ' . $time));
    }

    public static function overlaps(
        Carbon $requestedStart,
        Carbon $requestedEnd,
        Carbon $existingStart,
        Carbon $existingEnd
    ): bool {
        return $requestedStart->lt($existingEnd) && $requestedEnd->gt($existingStart);
    }
}
