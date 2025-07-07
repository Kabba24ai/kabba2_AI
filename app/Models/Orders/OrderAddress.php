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

    protected $appends = [
        'full_name',
        'full_address',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getFullAddressAttribute()
    {
        $addressParts = [
            $this->address,
            $this->city,
            $this->state,
            $this->zip_code,
        ];

        return implode(', ', array_filter($addressParts));
    }

    /**
     * Check if the delivery (Shipping) address is the same as the billing address for the given order.
     *
     * @param Order $order
     * @return bool
     */
    public function isSameAs($address)
    {
        if (!$address) {
            return false;
        }

        // Compare relevant fields for equality (customize as needed)
        return
            $this->full_name === $address->full_name &&
            $this->email === $address->email &&
            $this->phone === $address->phone &&
            $this->full_address === $address->full_address;
    }
}
