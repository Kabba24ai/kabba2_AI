<?php

namespace App\Models\Orders;

use App\Models\Customers\SalesFunnel;
use Illuminate\Database\Eloquent\Model;

class OrderProductFunnelLog extends Model
{
    protected $table = 'order_product_funnel_logs';

    protected $fillable = [
        'order_product_id',
        'product_id',
        'sales_funnel_id',
        'sales_funnel_step_id',
        'step_type',
        'step_name',
        'message',
        'status',
        'sent_at',
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
