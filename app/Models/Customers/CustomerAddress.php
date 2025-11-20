<?php

namespace App\Models\Customers;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;

// Models
use App\Models\Locations\State;

class CustomerAddress extends Model
{
     protected $fillable = [
        'unique_id',
        'type', // Billing*, Shipping
        'customer_id',
        'is_primary',
        'first_name',
        'last_name',
        'country',
        'phone',
        'address',

        'state_id',
        'city',
        'zip_code'
    ];

    protected $appends = [
        'full_name',
        'full_address',
        'state_name',
        'email'
    ];

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'CUST-ADD');
        });
    }

    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getFullAddressAttribute()
    {
        return trim("{$this->address}, {$this->city}, {$this->state->name}, {$this->zip_code}");
    }

    public function getStateNameAttribute()
    {
        return $this->state ? $this->state->name : null;
    }

    public function getEmailAttribute()
    {
        return $this->customer ? $this->customer->email : null;
    }

    public static function setPrimaryAddress(CustomerAddress $address)
    {
        // Unset previous primary addresses for this customer and type
        self::where('customer_id', $address->customer_id)
            ->where('type', $address->type)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);

        // Set the given address as primary
        $address->is_primary = true;
        $address->save();
    }
}
