<?php

namespace App\Models\Orders;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;

// enums
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;

class OrderPayment extends Model
{
    // Allow mass assignment for these fields
    protected $fillable = [
        'parent_order_payment_id',
        'unique_id',
        'order_id',
        'payment_method', // e.g., 'COD*', 'Account', 'Card'
        'payment_datetime',
        'transaction_id',
        'card_first_name',
        'card_last_name',
        'card_number',
        'auth_code',
        'customer_profile_id',
        'payment_profile_id',
        'payment_note',
        'amount',
        'status', // e.g., 'Pending', 'Paid', 'Account', 'Partial Refund', 'Refunded', 'Failed'
        'refund_amount',
        'refund_note',
        'created_by_id',
        'created_by_type',
        'updated_by_id',
        'updated_by_type',
    ];

    protected $casts = [
        'payment_method' => OrderPaymentMethod::class,
        'status' => OrderPaymentStatus::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'ORD-PAY');
        });
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

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', OrderPaymentStatus::Pending);
    }

    public function scopeRefund($query)
    {
        return $query->whereIn('status', [OrderPaymentStatus::PartialRefund, OrderPaymentStatus::Refund]);
    }

    public function scopePaid($query)
    {
        return $query->where('status', OrderPaymentStatus::Paid);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', OrderPaymentStatus::Failed);
    }

    public function scopeCod($query)
    {
        return $query->where('payment_method', OrderPaymentMethod::COD);
    }

}
