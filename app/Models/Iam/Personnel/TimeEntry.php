<?php

namespace App\Models\Iam\Personnel;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class TimeEntry extends Model
{
    use HasFactory;

    protected $table = 'time_entries';

    protected $fillable = [
        'employee_id',
        'clock_in',
        'clock_out',
        'break_duration',
        'notes',
        'status',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(
            User::class,
            'employee_id'
        );
    }
}
