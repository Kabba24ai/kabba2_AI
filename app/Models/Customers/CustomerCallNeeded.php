<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Iam\Personnel\User;

class CustomerCallNeeded extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'reason',
        'notes',
        'status',
        'created_by',
        'is_urgent',
        'auth_by',
        'contact_name',
        'contact_email',
        'contact_phone'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
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
}