<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;

class OrderProductDamageChargeLog extends Model
{
    protected $fillable = [
        'order_product_id',
        'before_amount',
        'change_amount',
        'after_amount',
        'action',
        'user_id',
        'note',
    ];

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\Iam\Personnel\User::class);
    }
}
