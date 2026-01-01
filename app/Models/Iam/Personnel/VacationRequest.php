<?php

namespace App\Models\Iam\Personnel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VacationRequest extends Model
{
    use HasFactory;

    protected $table = 'vacation_requests';

    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'vacation_request_hour_id',
        'request_type',
        'status',
        'notes',
        'approved_by',
        'approved_at',
        'denial_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    /* =======================
     | Relationships
     ======================= */

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function hourOption()
    {
        return $this->belongsTo(
            VacationRequestHour::class,
            'vacation_request_hour_id'
        );
    }
    public function requestHour()
    {
        return $this->belongsTo(
            VacationRequestHour::class,
            'vacation_request_hour_id'
        );
    }


    /* =======================
     | Helpers
     ======================= */

    public function getHoursAttribute(): float
    {
        return (float) optional($this->hourOption)->hours ?? 0;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isDenied(): bool
    {
        return $this->status === 'denied';
    }
}
