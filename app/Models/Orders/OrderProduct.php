<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// Enums
use App\Enums\Orders\EquipmentDriverStatus;
use App\Enums\Orders\OrderMediaType;
use App\Enums\Orders\OrderProductChargeStatus;

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

    /**
     * Administrative closure status (delivery_status / pickup_status): the
     * leg was closed WITHOUT the physical run happening — cancelled
     * bookings, corrections, refunded-before-delivery orders. It sets
     * is_delivered/is_returned like a real completion, so any metric
     * counting PHYSICAL work must exclude this status explicitly rather
     * than trusting the booleans (see DeliveryPerformanceEngine).
     */
    public const STATUS_CLOSE_AS_COMPLETED = 'Close as Completed';

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
        'special_tax',
        'added_fees',
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
        'delivery_load_id',
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
        'pickup_load_id',
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
        'is_product_clean',
        'rental_prepaid_cleaning',
        'delivery_clean_option',
        'delivery_clean_id',
        'return_clean_option',
        'return_clean_id',
        'total_clean_charge',

        'equipment_id',
        'equipment_details',
        'assigned_by',
        'assigned_at',

        // TD-4 (Phase 3 tech debt): JSON driver pre-delivery SOP checklist state
        // (customer called/texted, keys location, fuel level, dispatched product
        // options). Despite the name, this has ZERO relation to ChecklistMaster,
        // RentalReadyChecklistTemplate, or CustomerAdminTemplate — a completely
        // separate "checklist" concept that happens to share the English word.
        // Written/read only by Dispatch\ShowController. See
        // docs/checklist-system-audit/P3_5_TECHNICAL_DEBT.md.
        'dispatch_checklist',
        'dispatch_delivery_date',
        'dispatch_return_date',

        'delivery_equipment_fuel',
        'delivery_equipment_key_location',
        'delivery_equipment_driver_status',
        'pickup_equipment_fuel',
        'pickup_equipment_key_location',
        'pickup_equipment_driver_status',
        'delivery_ready_to_go_at',
        'delivery_on_my_way_at',
        'delivery_arrived_at',
        'delivery_is_delivered',
        'delivery_is_arrived',
        'pickup_ready_to_go_at',
        'pickup_on_my_way_at',
        'pickup_arrived_at',
        'pickup_is_delivered',
        'pickup_is_arrived',

        'delivery_inputs_date',
        'delivery_tnc_status',
        'delivery_drivers_license_status',
        'delivery_video_status',
        'delivery_checklist_status',
        'pickup_inputs_date',
        'pickup_tnc_status',
        'pickup_drivers_license_status',
        'pickup_video_status',
        'pickup_checklist_status',
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
        'delivery_equipment_driver_status' => EquipmentDriverStatus::class,
        'pickup_equipment_driver_status'   => EquipmentDriverStatus::class,
        'dispatch_delivery_date' => 'date',
        'dispatch_return_date'   => 'date',
        'delivery_ready_to_go_at' => 'datetime',
        'delivery_on_my_way_at'   => 'datetime',
        'delivery_arrived_at'     => 'datetime',
        'delivery_is_delivered'   => 'boolean',
        'delivery_is_arrived'     => 'boolean',
        'pickup_ready_to_go_at'   => 'datetime',
        'pickup_on_my_way_at'     => 'datetime',
        'pickup_arrived_at'       => 'datetime',
        'pickup_is_delivered'     => 'boolean',
        'pickup_is_arrived'       => 'boolean',
        'fuel_charge_status'      => OrderProductChargeStatus::class,
        'damage_status'           => OrderProductChargeStatus::class,
        'is_product_clean'        => 'boolean',
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

            // Stop funnel SMS for this product. If the parent Order::deleting hook already
            // created 'Stopped' entries (order deletion path), the service's exists() check
            // prevents duplicates. If this is a standalone product removal, it creates them.
            \App\Services\FunnelLifecycleService::stopAllFunnelsForProduct(
                $model,
                \App\Services\FunnelLifecycleService::REASON_PRODUCT_REMOVED
            );

            $model->softAssignment()->delete();
            $model->orderMedia()->delete();

            $model->checklistQuestions()->get()->each(function ($question) {
                $question->answers()->delete();
            });

            $model->checklistQuestions()->delete();
        });

        // When delivery_status changes to Completed, stop all delivery-reminder funnels.
        // Delivery reminders are before-event steps (offset_direction = 'before') only.
        // Post-delivery funnels (review requests, return reminders) are intentionally unaffected.
        static::updated(function ($model) {
            if ($model->wasChanged('delivery_status') && $model->delivery_status === 'Completed') {
                // Determine early vs on-schedule delivery
                $originalDate = $model->getOriginal('delivery_date');
                $originalTime = $model->getOriginal('delivery_time');
                $reason       = \App\Services\FunnelLifecycleService::REASON_DELIVERY_COMPLETED;

                if ($originalDate && $originalTime) {
                    $scheduled = \Carbon\Carbon::parse("{$originalDate} {$originalTime}");
                    if (now()->lt($scheduled)) {
                        $reason = \App\Services\FunnelLifecycleService::REASON_DELIVERY_COMPLETED_EARLY;
                    }
                }

                \App\Services\FunnelLifecycleService::stopDeliveryReminderFunnels($model, $reason);
            }
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
     * Canonical "has this equipment entered the field?" test — the safety
     * gate for automatic operational closure (RefundedOrderScheduleCloser).
     *
     * Evidence, strongest first:
     *  - delivery_status 'Completed'  — every real delivery path writes it
     *    (customer checklist, assign-equipment, manual status change, API)
     *  - is_delivered                 — written in lockstep with the above;
     *    kept as drift protection. NOTE: 'Close as Completed' also sets it,
     *    so administratively closed rows read as delivered here — callers
     *    that must distinguish administrative closure check delivery_status
     *    for the literal 'Close as Completed' FIRST (see the closer).
     *  - delivery_is_delivered / delivery_arrived_at — driver-app "Arrived"
     *    signals. Not authoritative for delivery, but the equipment may be
     *    on a truck at the customer's site, so for safety it counts as
     *    having entered the field.
     *
     * Deliberately NOT evidence: terms/license/video completion (pre-delivery
     * gating steps), dispatch planning fields (dispatch_delivery_date etc. are
     * routing artifacts written before anything moves), and checklist-row
     * existence (~31% of delivered products are checklist-exempt admin
     * closures — see UpdateProductScheduleController).
     */
    public function hasBeenDelivered(): bool
    {
        if ($this->delivery_status === 'Completed' || $this->delivery_status === 'Close as Completed') {
            return true;
        }

        if ($this->is_delivered) {
            return true;
        }

        return (bool) $this->delivery_is_delivered || $this->delivery_arrived_at !== null;
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

    public function deliveryLoad()
    {
        return $this->belongsTo(\App\Models\Dispatch\DispatchLoad::class, 'delivery_load_id');
    }

    public function pickupLoad()
    {
        return $this->belongsTo(\App\Models\Dispatch\DispatchLoad::class, 'pickup_load_id');
    }

    public function queueLineItem()
    {
        return $this->hasOne(QueueLineItem::class, 'order_product_id', 'id');
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
     * CustomerAccount charge records originating from this OrderProduct.
     */
    public function customerAccountCharges()
    {
        return $this->hasMany(\App\Models\Customers\CustomerAccount::class, 'order_product_id')
            ->where('type', 'charge');
    }

    /**
     * BillingCharge records linked to this OrderProduct.
     */
    public function billingCharges()
    {
        return $this->hasMany(\App\Models\Orders\BillingCharge::class, 'order_product_id');
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
