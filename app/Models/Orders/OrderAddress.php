<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;

class OrderAddress extends Model
{
    protected $fillable = [
        'order_id',
        'type', // Billing*, Shipping
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'state_id',
        'zip_code',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
