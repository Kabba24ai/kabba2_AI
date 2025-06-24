<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Model;

// Models
use App\Models\Locations\State;

class CustomerAddress extends Model
{
     protected $fillable = [
        'unique_id',
        'type', // Billing*, Shipping
        'customer_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'state_id',
        'city',
        'zip_code'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
