<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Model;
use App\Models\Iam\Personnel\User;

class CustomerCallNeededActivity extends Model
{
    protected $fillable = [
        'customer_call_needed_id',
        'status',
        'notes',
        'follow_up_date',
        'created_by',
    ];

    public function callNeeded()
    {
        return $this->belongsTo(
            CustomerCallNeeded::class,
            'customer_call_needed_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}