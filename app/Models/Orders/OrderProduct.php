<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// Enums
use App\Enums\Orders\EquipmentDriverStatus;
use App\Enums\Orders\OrderMediaType;

// Helpers
use App\Helpers\ModelHelper;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate;
use App\Models\Global\Media;
use App\Models\MaintenanceManagement\Equipment;

// Models
use App\Models\ProductManagement\Product;
use App\Models\Stores\Store;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\EquipmentSoftAssign;

class OrderProduct extends Model
{
    use SoftDeletes;

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
        'delivery_priority',
        'delivery_driver_locked',
        'delivery_priority_locked',
        'delivery_signature_media_id',
        'delivery_notes',
        'is_delivered',

        'pickup_status', // 'Pending',  'Completed', 'Reschedule'
        'pickup_transport_mode', // 'Store', 'Truck'
        'pickup_store_id',
        'pickup_date',
        'pickup_time',
        'pickup_by',
        'pickup_priority',
        'pickup_driver_locked',
        'pickup_priority_locked',
        'pickup_signature_media_id',
        'pickup_notes',
        'is_returned',

        'start_hours',
        'end_hours',
        'fuel_initial_reading',
        'fuel_final_reading',
        'fuel_total_charge',
        'fuel_charge_status',
        'total_charge',
        'damage_charge',
        'damage_status',

        'equipment_id',
        'equipment_details',
        'assigned_by',
        'assigned_at',

        'dispatch_checklist',   // JSON: driver pre-delivery checklist state

        'equipment_fuel',
        'equipment_key_location',
        'equipment_driver_status',
    ];

    // In your OrderProduct.php model
    protected $casts = [
        'product_data'       => 'array',
        'equipment_details'  => 'array',
        'dispatch_checklist' => 'array',
        'delivery_driver_locked'   => 'boolean',
        'delivery_priority_locked' => 'boolean',
        'pickup_driver_locked'     => 'boolean',
        'pickup_priority_locked'   => 'boolean',
        'equipment_driver_status' => EquipmentDriverStatus::class,
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

    public function funnelLogs()
    {
        return $this->hasMany(OrderProductFunnelLog::class, 'order_product_id');
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

    public function orderMedia()
    {
        return $this->hasMany(OrderMedia::class, 'order_product_id');
    }

    public function scopeWithActiveOrder($query)
    {
        return $query->whereHas('order');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'ORD-SCH');
        });

        static::deleting(function ($model) {
            if ($model->isForceDeleting()) {
                return;
            }

            $model->softAssignment()->delete();
            $model->orderMedia()->delete();

            $model->checklistQuestions()->get()->each(function ($question) {
                $question->answers()->delete();
            });

            $model->checklistQuestions()->delete();
        });

        static::restoring(function ($model) {
            $model->softAssignment()->withTrashed()->restore();
            $model->orderMedia()->withTrashed()->restore();

            $model->checklistQuestions()->withTrashed()->get()->each(function ($question) {
                $question->answers()->withTrashed()->restore();
            });

            $model->checklistQuestions()->withTrashed()->restore();
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

    public function softAssignment()
    {
        return $this->hasOne(EquipmentSoftAssign::class, 'order_product_id', 'id')->with('equipment');
    }

    public function softEquipment()
    {
        return $this->hasOneThrough(
            Equipment::class,             // final model
            EquipmentSoftAssign::class,   // intermediate model
            'order_product_id',           // FK on soft_assign (intermediate) referencing order_products.id
            'id',                         // FK on equipment (final model)
            'id',                         // local key on order_products
            'equipment_id'                // final key on soft_assign
        );
    }

    public function equipmentLocation()
    {
        if ($this->equipment?->status_label === 'Rented') {
            return $this->order?->customer_name ?? '-';
        }

        return $this->softAssignment?->equipment?->store?->store_name ?? '-';
    }

    public function damageChargeLogs()
    {
        return $this->hasMany(
            OrderProductDamageChargeLog::class
        )->latest();
    }




    /**
     * Current damage owed (base + adjustments)
     */
    public function getCurrentDamageChargeAttribute(): float
    {
        $base = (float) ($this->damage_charge ?? 0);

        $adjustments = (float) $this->damageChargeLogs()
            ->sum('change_amount');

        return max(0, $base + $adjustments);
    }


    public function fuelChargeLogs()
    {
        return $this->hasMany(OrderProductFuelChargeLog::class)->latest();
    }


    /**
     * Current fuel owed (base + adjustments)
     */
    public function getCurrentFuelChargeAttribute(): float
    {
        $base = (float) ($this->fuel_total_charge ?? 0);

        $adjustments = (float) $this->fuelChargeLogs()
            ->sum('change_amount');

        return max(0, $base + $adjustments);
    }
}
