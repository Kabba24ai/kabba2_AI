<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;

// Models
use App\Models\ProductManagement\Product;
use App\Models\Stores\Store;

class OrderProduct extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'price',
        'quantity',
        'sub_total',
        'tax',
        'total',
        'schedule_start_date',
        'schedule_end_date',
        'product_data', // json
        'service_method', // 'In Store Pickup', 'Delivery'
        'service_option', // 'Delivery + Pickup', 'Delivery + Return', 'Pickup + Return'
        'store_id',
        'distance_type', // 'Standard', 'Extended', 'Custom'
        'distance_range',
    ];

    // In your OrderProduct.php model
    protected $casts = [
        'product_data' => 'array',
    ];

    // Relationships

    /**
     * Get the order that owns the order product
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product associated with the order product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the store associated with the order product
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
