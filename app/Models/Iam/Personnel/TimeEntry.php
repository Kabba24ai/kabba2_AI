<?php

namespace App\Models\Iam\Personnel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'clock_in',
        'clock_out',
        'break_duration',
        'notes',
        'status',
        'total_hours',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

//     protected static function booted()
// {
//     static::saving(function ($entry) {

//         if ($entry->clock_in && $entry->clock_out) {

//             $clockIn  = Carbon::parse($entry->clock_in);
//             $clockOut = Carbon::parse($entry->clock_out);

//             if ($clockOut->lessThanOrEqualTo($clockIn)) {
//                 $entry->total_hours = 0;
//                 return;
//             }

//             //  CORRECT ORDER
//             $seconds =
//                 $clockIn->diffInSeconds($clockOut)
//                 - ((int) $entry->break_duration * 60);

//             $entry->total_hours = round(max($seconds, 0) / 3600, 2);
//         } else {
//             $entry->total_hours = 0;
//         }
//     });
// }



    protected static function booted()
    {
        static::saving(function ($entry) {

            if (!$entry->clock_in || !$entry->clock_out) {
                $entry->total_hours = 0;
                return;
            }

            $clockIn  = Carbon::parse($entry->clock_in);
            $clockOut = Carbon::parse($entry->clock_out);

            if ($clockOut->lessThanOrEqualTo($clockIn)) {
                $entry->total_hours = 0;
                return;
            }

            // Total worked time
            $workedSeconds = $clockIn->diffInSeconds($clockOut);

            // Subtract ALL breaks
            $breakSeconds = $entry->breaks()
                ->whereNotNull('end_time')
                ->get()
                ->sum(fn ($b) =>
                    Carbon::parse($b->start_time)
                        ->diffInSeconds(Carbon::parse($b->end_time))
                );

            $netSeconds = max($workedSeconds - $breakSeconds, 0);

            $entry->total_hours = round($netSeconds / 3600, 2);
        });
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }


    public function breaks()
    {
        return $this->hasMany(TimeEntryBreak::class);
    }

    public function activeBreak()
    {
        return $this->hasOne(TimeEntryBreak::class)
            ->whereNull('end_time');
    }

}
