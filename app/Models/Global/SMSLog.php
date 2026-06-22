<?php

namespace App\Models\Global;

use App\Enums\Communication\SmsType;
use App\Models\Customers\Customer;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\Orders\PodPaymentLink;
use Illuminate\Database\Eloquent\Model;

class SMSLog extends Model
{
    protected $table = 'sms_logs';

    protected $fillable = [
        'sms_type',
        'status',
        'phone',
        'message',
        'sms_sent_at',
        'customer_id',
        'order_product_id',
        'order_id',
        'pod_payment_link_id',
        'twilio_sid',
        'error_message',
    ];

    protected $casts = [
        'sms_sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'sms_type' => SmsType::class,
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function podPaymentLink()
    {
        return $this->belongsTo(PodPaymentLink::class);
    }

    // Query Scopes
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('sms_type', $type);
    }

    public function scopeByOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}

