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
        'order_time', // New column for order time
        'customer_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'subtotal',
        'is_tax_exempt', // Yes, No*
        'tax_amount',
        'coupon_code',
        'discount_amount',
        'grand_total',
        'order_note',
        'cart_data', // JSON data of cart items
        'platform', // Web*, Android, iOS
    ];

    protected $casts = [
        'cart_data' => 'array',
    ];

    protected $appends = [
        'view_link', // For generating view link in schedules
        'last_payment_type',
        'last_payment_status',
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

    public function billingAddress()
    {
        return $this->hasOne(OrderAddress::class, 'order_id')->where('type','Billing');
    }

    public function products()
    {
        return $this->hasMany(OrderProduct::class, 'order_id');
    }

    public function payments()
    {
        return $this->hasMany(OrderPayment::class, 'order_id');
    }

    public function lastPayment()
    {
        return $this->hasOne(OrderPayment::class, 'order_id')->latest('id');
    }

    public function history()
    {
        return $this->hasMany(OrderHistory::class, 'order_id');
    }

    public function notes()
    {
        return $this->hasMany(OrderNote::class, 'order_id')->latest('id');
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

            // Format: ORD-0001
            $model->order_number = '#' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
            // Set current date and time
            $currentDateTime = Carbon::now();
            $model->order_date = $currentDateTime->format(config('app.date.db_date_format'));
            $model->order_time = $currentDateTime->format('H:i:s');
        });
    }

    public function getViewLinkAttribute()
    {
        $url = route('admin.order-management.orders.edit', ['unique_id' => $this->unique_id]);
        return '<a href="' . $url . '" class="text-brand-500 underline font-bold">' . $this->order_number . '</a>';
    }

    public function getLastPaymentTypeAttribute()
    {
        $lastPayment = $this->lastPayment;
        return $lastPayment ? $lastPayment->payment_method : null;
    }

    public function getLastPaymentStatusAttribute()
    {
        $lastPayment = $this->lastPayment;
        return $lastPayment ? $lastPayment->status->label() : null;
    }

}
