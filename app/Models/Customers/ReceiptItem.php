<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Model;
use App\Models\Orders\OrderProduct;

class ReceiptItem extends Model
{
    protected $fillable = [
        'receipt_id',
        'type',
        'item_name',
        'unit',
        'qty',
        'tax',
        'total',
        'item_id',
    ];

    /** Relationships */

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }


    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class, 'item_id', 'unique_id');
    }
}
