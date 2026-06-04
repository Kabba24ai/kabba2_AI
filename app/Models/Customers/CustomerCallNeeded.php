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
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}