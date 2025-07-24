<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;

// Helpers
use App\Helpers\ModelHelper;

// Models
use App\Models\ProductManagement\Product;
use App\Models\Stores\Store;

class OrderProduct extends Model
{
    protected $fillable = [
        'unique_id',
        'order_id',
        'product_id',
        'product_name',
        'price',
        'quantity',
        'sub_total',
        'tax',
        'total',
        'product_data', // json
        'service_method', // 'In Store Pickup', 'Delivery'
        'service_option', // 'Delivery + Pickup', 'Delivery Only', 'Return Only'
        'store_id',
        'distance_type', // 'Standard', 'Extended', 'Custom'
        'distance_range',

        'delivery_status',
        'delivery_transport_mode',
        'delivery_store_id',
        'delivery_by',
        'delivery_date',
        'delivery_time',

        'pickup_status',
        'pickup_transport_mode',
        'pickup_store_id',
        'pickup_date',
        'pickup_time',
        'pickup_by',
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'ORD-SCH');
        });
    }

}
