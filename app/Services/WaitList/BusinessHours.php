<?php

namespace App\Services\WaitList;

use Illuminate\Support\Carbon;

/** Business-hours window checks for wait list push delivery (config/waitlist.php). */
class BusinessHours
{
    public static function isOpen(?Carbon $at = null): bool
    {
        $at    = $at ?? now();
        $hours = config('waitlist.business_hours')[$at->dayOfWeek] ?? null;

        if (!$hours) {
            return false;
        }

        [$open, $close] = $hours;

        return $at->gte($at->copy()->setTimeFromTimeString($open))
            && $at->lt($at->copy()->setTimeFromTimeString($close));
    }

    /** The next moment pushes may be delivered (now, if currently open). */
    public static function nextOpening(?Carbon $from = null): Carbon
    {
        $from = $from ?? now();

        if (self::isOpen($from)) {
            return $from->copy();
        }

        $cursor = $from->copy();
        for ($i = 0; $i < 8; $i++) {
            $hours = config('waitlist.business_hours')[$cursor->dayOfWeek] ?? null;

            if ($hours) {
                $open = $cursor->copy()->setTimeFromTimeString($hours[0]);
                if ($open->gt($from)) {
                    return $open;
                }
            }

            $cursor->addDay()->startOfDay();
        }

        return $from->copy()->addDay(); // unreachable with a sane config
    }
}
