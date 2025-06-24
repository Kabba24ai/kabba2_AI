<?php

namespace App\Models\Orders;

use App\Models\Customers\Customer;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'unique_id',
        'order_number',
        'order_date',
        'customer_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'subtotal',
        'tax_amount',
        'coupon_code',
        'discount_amount',
        'grand_total',
        'payment_type', // COD*, Account, Card
        'status', // Pending, In Progress, Completed, Cancelled
        'platform', // Web*, Android, iOS
        'created_by_id',
        'created_by_type',
        'updated_by_id',
        'updated_by_type',
    ];
    // Customer relationship (if you want)
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Polymorphic relations for created_by and updated_by
    public function createdBy()
    {
        return $this->morphTo();
    }

    public function updatedBy()
    {
        return $this->morphTo();
    }
}
