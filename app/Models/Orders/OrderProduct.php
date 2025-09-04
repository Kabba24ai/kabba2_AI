<?php

namespace App\Models\Orders;

use App\Enums\Orders\OrderMediaType;
use Illuminate\Database\Eloquent\Model;

// Helpers
use App\Helpers\ModelHelper;
use App\Models\MaintenanceManagement\Equipment;
// Models
use App\Models\ProductManagement\Product;

class OrderProduct extends Model
{
    protected $fillable = [
        'unique_id',
        'order_id',
        'product_id',
        'product_name',
        'price',
        'quantity',
        'allocated_hours',
        'sub_total',
        'tax',
        'total',
        'product_data', // json
        'service_method', // 'In Store Pickup', 'Delivery'
        'service_option', // 'Delivery + Pickup', 'Delivery Only', 'Return Only'
        'distance_type', // 'Standard', 'Extended', 'Custom'
        'distance_range',

        'delivery_status', // 'Pending',  'Completed', 'Reschedule'
        'delivery_transport_mode', // 'Store', 'Truck'
        'delivery_store_id',
        'delivery_by',
        'delivery_date',
        'delivery_time',

        'pickup_status', // 'Pending',  'Completed', 'Reschedule'
        'pickup_transport_mode', // 'Store', 'Truck'
        'pickup_store_id',
        'pickup_date',
        'pickup_time',
        'pickup_by',
        'equipment_id',
        'equipment_details',
        'assigned_by',
        'assigned_at'
    ];

    // In your OrderProduct.php model
    protected $casts = [
        'product_data' => 'array',
        'equipment_details' => 'array',
    ];

    // Relationships

    /**
     * Get the order that owns the order product
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * Get the product associated with the order product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function deliveryMedia()
    {
        return $this->hasMany(OrderMedia::class)->where('type', OrderMediaType::DELIVERY);
    }

    public function pickupMedia()
    {
        return $this->hasMany(OrderMedia::class)->where('type', OrderMediaType::PICKUP);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'ORD-SCH');
        });
    }

    /**
     * Reverse mapping: from transport modes to service_method and service_option.
     *
     * @return array
     */
    public function getServiceMethodFromTransportMode()
    {
        $deliveryTransportMode = $this->delivery_transport_mode;
        $pickupTransportMode = $this->pickup_transport_mode;

        $serviceMethod = 'In Store Pickup';
        $serviceOption = null;

        if ($deliveryTransportMode === 'Store' && $pickupTransportMode === 'Store') {
            $serviceMethod = 'In Store Pickup';
        } elseif ($deliveryTransportMode === 'Truck' && $pickupTransportMode === 'Truck') {
            $serviceMethod = 'Delivery';
            $serviceOption = 'Delivery + Pickup';
        } elseif ($deliveryTransportMode === 'Truck' && $pickupTransportMode === 'Store') {
            $serviceMethod = 'Delivery';
            $serviceOption = 'Delivery Only';
        } elseif ($deliveryTransportMode === 'Store' && $pickupTransportMode === 'Truck') {
            $serviceMethod = 'Delivery';
            $serviceOption = 'Return Only';
        } else {
            $serviceMethod = null;
            $serviceOption = null;
        }

        return [
            'service_method' => $serviceMethod,
            'service_option' => $serviceOption,
        ];
    }


}
