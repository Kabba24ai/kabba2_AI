<?php

namespace App\Models\Orders;

use App\Helpers\ModelHelper;
use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// enums
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;

class OrderPayment extends Model
{
    use SoftDeletes;

    // Allow mass assignment for these fields
    protected $fillable = [
        'parent_order_payment_id',
        'unique_id',
        'order_id',
        'payment_method', // e.g., 'COD*', 'Account', 'Card'
        'payment_datetime',
        'refunded_at',
        'transaction_id',
        'gateway_refund_id',
        'card_first_name',
        'card_last_name',
        'card_number',
        'auth_code',
        'customer_profile_id',
        'payment_profile_id',
        'cheque_number',
        'payment_note',
        'amount',
        'status', // e.g., 'Pending', 'Paid', 'Account', 'Partial Refund', 'Refunded', 'Failed'
        'refund_amount',
        'tax_refunded',
        'refund_note',
        'payment_response',
        'voided_at',
        'processed_by_id',
        'processed_by_name',
        'processed_reason_code',
        'processed_reason_label',
        'processed_reason_other',
        'created_by_id',
        'created_by_type',
        'updated_by_id',
        'updated_by_type',
    ];

    protected $casts = [
        'payment_method'   => OrderPaymentMethod::class,
        'status'           => OrderPaymentStatus::class,
        'payment_response' => 'json',
        'payment_datetime' => 'datetime',
        'refunded_at'      => 'datetime',
        'voided_at'        => 'datetime',
        'tax_refunded'     => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'ORD-PAY');
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
