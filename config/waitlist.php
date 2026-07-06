<?php

/*
|--------------------------------------------------------------------------
| Equipment Wait List
|--------------------------------------------------------------------------
| Push notifications are only delivered during business hours; alerts
| created after hours defer their push to the next opening. The second
| push fires when the first goes unacknowledged for the delay below.
*/

return [

    // 0 = Sunday … 6 = Saturday; null = closed. Times are local (app TZ).
    'business_hours' => [
        0 => null,                  // Sunday closed
        1 => ['07:00', '17:00'],    // Monday
        2 => ['07:00', '17:00'],
        3 => ['07:00', '17:00'],
        4 => ['07:00', '17:00'],
        5 => ['07:00', '17:00'],    // Friday
        6 => ['07:00', '12:00'],    // Saturday
    ],

    'second_push_delay_minutes' => 30,

];
