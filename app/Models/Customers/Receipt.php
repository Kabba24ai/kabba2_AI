<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Helpers\ModelHelper;

class Receipt extends Model
{
    protected $fillable = [
        'unique_id',
        'invoice_id',
        'customer_id',
        'order_id',
        'receipt_created_by',
        'payment_method',
        'receipt_date',
        'order_date',
        'payment_status',
        'subtotal',
        'sales_tax',
        'total',
        'is_email_status',
        'mail_send_at',
    ];

    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'REC');
        });
    }

    /** Relationships */

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(\App\Models\Orders\Order::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\Iam\Personnel\User::class, 'receipt_created_by');
    }

    public function items()
    {
        return $this->hasMany(ReceiptItem::class);
    }
}
