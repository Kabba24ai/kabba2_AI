<?php

namespace App\Models\Iam\Personnel;

use Illuminate\Database\Eloquent\Model;

class TimeEntryBreak extends Model
{
    protected $fillable = [
        'time_entry_id',
        'type',          // ['lunch', 'other']
        'start_time',
        'end_time',
    ];

     protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

     public function timeEntry()
        {
            return $this->belongsTo(TimeEntry::class);
        }
}
