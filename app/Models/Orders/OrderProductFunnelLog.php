<?php

namespace App\Models\Orders;

use App\Models\Customers\SalesFunnel;
use Illuminate\Database\Eloquent\Model;

class OrderProductFunnelLog extends Model
{
    protected $table = 'order_product_funnel_logs';

    protected $fillable = [
        'order_product_id',
        'order_id',
        'customer_id',
        'product_id',
        'sales_funnel_id',
        'sales_funnel_step_id',
        'step_type',
        'step_name',
        'message',
        'notes',
        'status',
        'sent_at',
        'twilio_sid',
        'sms_timezone',
        'lifecycle_reason',
        'stopped_at',
    ];

    protected $casts = [
        'sent_at'    => 'datetime',
        'stopped_at' => 'datetime',
    ];

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id');
    }

    public function salesFunnel()
    {
        return $this->belongsTo(SalesFunnel::class, 'sales_funnel_id');
    }
}
