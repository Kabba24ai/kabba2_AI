<?php

namespace App\Models\Customers;

use App\Enums\Tasks\TaskCategory;
use App\Enums\Tasks\TaskPriority;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Iam\Personnel\User;

class CustomerCallNeeded extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'supplier_id',
        'reason',
        'category',
        'notes',
        'status',
        'created_by',
        'is_urgent',
        'priority',
        'due_date',
        'auth_by',
        'contact_name',
        'contact_email',
        'contact_phone',
        'follow_up_at',
        'follow_up_note',
        'rescheduled_by',
        'rescheduled_at',
    ];

    protected $casts = [
        'follow_up_at'   => 'datetime',
        'rescheduled_at' => 'datetime',
        'priority'       => TaskPriority::class,
        'category'       => TaskCategory::class,
        'due_date'       => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier()
    {
        return $this->belongsTo(\App\Models\MaintenanceManagement\Supplier::class);
    }

   public function creator()
{
    return $this->belongsTo(User::class, 'auth_by');
}

public function assignee()
{
    return $this->belongsTo(User::class, 'created_by');
}

public function notes()
{
    return $this->hasMany(CustomerNote::class, 'customer_call_needed_id');
}



public function activities()
{
    return $this->hasMany(
        CustomerCallNeededActivity::class,
        'customer_call_needed_id'
    );
}

public function latestActivity()
{
    return $this->hasOne(
        CustomerCallNeededActivity::class,
        'customer_call_needed_id'
    )->latestOfMany();
}


}