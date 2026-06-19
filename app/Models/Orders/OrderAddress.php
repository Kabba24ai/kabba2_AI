<?php

namespace App\Models\Orders;

use App\Models\Locations\State;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderAddress extends Model
{
    use SoftDeletes;

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

    public function stateDetail()
    {
        return $this->belongsTo(State::class, 'state_id');
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
            $this->stateDetail?->abbreviation ? $this->stateDetail->abbreviation : $this->state, // Assuming stateDetail is a relationship to a State model
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
