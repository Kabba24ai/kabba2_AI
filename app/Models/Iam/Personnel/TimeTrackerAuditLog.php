<?php

namespace App\Models\Iam\Personnel;

use Illuminate\Database\Eloquent\Model;

class TimeTrackerAuditLog extends Model
{
    protected $fillable = [
        'store_id',
        'employee_id',
        'actor_type',
        'actor_id',
        'entity_type',
        'entity_id',
        'action',
        'field',
        'old_value',
        'new_value',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
