<?php

namespace App\Helpers;


use Carbon\Carbon;
// Models
use App\Models\Configurations\TimeTrackerSetting;

class TimeTrackerHelper
{
     public static function roundNearest(Carbon $time, int $incrementMinutes): Carbon
    {
        $minutes = $time->minute;
        $remainder = $minutes % $incrementMinutes;

        $half = $incrementMinutes / 2;

        if ($remainder < $half) {
            // ROUND DOWN
            return $time->copy()
                ->subMinutes($remainder)
                ->second(0);
        } else {
            // ROUND UP
            return $time->copy()
                ->addMinutes($incrementMinutes - $remainder)
                ->second(0);
        }
    }
    
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

    public static function roundDown(Carbon $time, int $incrementMinutes): Carbon
    {
        $minutes = $time->minute;
        $remainder = $minutes % $incrementMinutes;

        return $time
            ->copy()
            ->subMinutes($remainder)
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

   public static function formatHoursToTime($hours)
{
    $totalMinutes = round($hours * 60);

    $hrs = floor($totalMinutes / 60);
    $mins = $totalMinutes % 60;

    return sprintf('%d:%02d', $hrs, $mins);
}

public static function formatSecondsToTime($seconds)
{
    $minutes = floor($seconds / 60);
    $hrs = floor($minutes / 60);
    $mins = $minutes % 60;

    return sprintf('%d:%02d', $hrs, $mins);
}

}
