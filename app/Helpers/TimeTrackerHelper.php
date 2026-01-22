<?php

namespace App\Helpers;


use Carbon\Carbon;
// Models
use App\Models\Configurations\TimeTrackerSetting;

class TimeTrackerHelper
{
     public static function roundUp(Carbon $time, int $incrementMinutes): Carbon
    {
        $minutes = $time->minute;
        $remainder = $minutes % $incrementMinutes;

        if ($remainder === 0) {
            return $time->copy()->second(0);
        }

        $minutesToAdd = $incrementMinutes - $remainder;

        return $time
            ->copy()
            ->addMinutes($minutesToAdd)
            ->second(0);
    }


       public static function getTimeTrackerSetting(string $key, mixed $default = null): mixed
    {
        $settings = TimeTrackerSetting::first();

        if (!$settings || !isset($settings->settings[$key])) {
            return $default;
        }

        return $settings->settings[$key];
    }

}
