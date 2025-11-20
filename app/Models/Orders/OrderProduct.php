<?php

namespace App\Models\Orders;

use App\Enums\Orders\OrderMediaType;
use Illuminate\Database\Eloquent\Model;

// Helpers
use App\Helpers\ModelHelper;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate;
use App\Models\Global\Media;
use App\Models\MaintenanceManagement\Equipment;
// Models
use App\Models\ProductManagement\Product;
use App\Models\Stores\Store;
use App\Models\Iam\Personnel\User;


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
        'hour_tracking',
        'hour_rate',
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
        'delivery_date',
        'delivery_time',
        'delivery_by',
        'delivery_signature_media_id',
        'delivery_notes',
        'is_delivered',

        'pickup_status', // 'Pending',  'Completed', 'Reschedule'
        'pickup_transport_mode', // 'Store', 'Truck'
        'pickup_store_id',
        'pickup_date',
        'pickup_time',
        'pickup_by',
        'pickup_signature_media_id',
        'pickup_notes',
        'is_returned',

        'start_hours',
        'end_hours',
        'fuel_initial_reading',
        'fuel_final_reading',
        'total_charge',

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

    public function deliverySignatureMedia()
    {
        return $this->belongsTo(Media::class, 'delivery_signature_media_id');
    }

    public function returnSignatureMedia()
    {
        return $this->belongsTo(Media::class, 'pickup_signature_media_id');
    }

    public function equipmentRentalReadyTemplate()
    {
        return $this->hasOne(EquipmentRentalReadyTemplate::class, 'order_product_id')->latestOfMany('id');
    }

    public function checklistQuestions()
    {
        return $this->hasMany(OrderProductChecklistQuestion::class);
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

    public function deliveryEmployee()
    {
        return $this->belongsTo(User::class, 'delivery_by');
    }

    public function pickupEmployee()
    {
        return $this->belongsTo(User::class, 'pickup_by');
    }

    public function deliveryStore()
    {
        return $this->belongsTo(Store::class, 'delivery_store_id');
    }

    public function pickupStore()
    {
        return $this->belongsTo(Store::class, 'pickup_store_id');
    }
}
