<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

// Helpers
use App\Helpers\ModelHelper;

// Models
use App\Models\Customers\Customer;

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
        'order_note',
        'status', // Pending, In Progress, Completed, Cancelled
        'platform', // Web*, Android, iOS
    ];

    // Customer relationship (if you want)
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function addresses()
    {
        return $this->hasMany(OrderAddress::class, 'order_id');
    }

    public function shippingAddress()
    {
        return $this->hasOne(OrderAddress::class, 'order_id')->where('type','Shipping');
    }

    public function products()
    {
        return $this->hasMany(OrderProduct::class, 'order_id');
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'ORD');

            // Get latest order ID
            $latestOrder = self::latest('id')->first();
            $nextId = $latestOrder ? $latestOrder->id + 1 : 1;

            // Format: ORD-00001
            $model->order_number = '#' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
            $model->order_date = Carbon::now()->format('Y-m-d');
        });
    }
}
