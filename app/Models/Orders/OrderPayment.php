<?php

namespace App\Models\Orders;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    // Allow mass assignment for these fields
    protected $fillable = [
        'unique_id',
        'order_id',
        'payment_method',
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
        'status',   // e.g., 'Pending', 'Completed', 'Failed'
        'refunded_amount',
        'refund_note',
        'created_by_id',
        'created_by_type',
        'updated_by_id',
        'updated_by_type',
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
        return $query->where('status', 'Pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'Completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'Failed');
    }

    public function scopeCod($query)
    {
        return $query->where('payment_method', 'COD');
    }

}
