<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Helpers
use App\Helpers\ModelHelper;
use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Enums\Equipments\EquipmentPowerSourceType;
use App\Enums\Equipments\EquipmentKeyStartingMechanism;

// Equipments
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplate;
use App\Models\Orders\Order;
use App\Models\ProductManagement\ProductCategory;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate;
use App\Models\Orders\OrderProduct;
use App\Models\Stores\Store;
use App\Models\MaintenanceManagement\EquipmentSpecification;

class Equipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'equipment';

    protected $fillable = [
        'unique_id',
        'equipment_name',
        'product_category_id',
        'equipment_id',
        'equipment_hours',
        'store_id',
        'is_tracked', // 'Yes', 'No'
        'overage_rate',
        'brand',
        'model',
        'model_year',
        'date_acquired',
        'purchase_cost',
        'down_payment',
        'amount_financed',
        'ownership_type', // owned, financed, leased
        'finance_company',
        'term_in_months',
        'interest_rate',
        'monthly_payment',
        'vehicle_identification_number',
        'serial_number',
        'license_plate',
        'imei',
        'power_source_type', // diesel, gas, batteries
        'has_def', // Yes, No
        'diesel_tank_capacity',
        'def_tank_capacity',
        'gas_tank_capacity',
        'standard_battery_count',
        'expanded_battery_count',
        'checklist_master_id',
        'equipment_service_id',
        'bring_service_flag',
        'bring_service_hour',
        'equipment_notes',
        'current_status', // available, rented, maintenance, damaged
        'current_status_changed_at',
        'current_order_id',
        'current_order_product_id',
        'current_status_updated_by',

        'not_for_rent',
        'volts',
        'amps',

        'created_by',
        'updated_by',

        'parts_list_id',
        'freight_shipping',
        'taxes_fees',
        'warranty_duration_months',
        'warranty_duration_hours',
        'key_starting_mechanism',
        'equipment_value',
        'coi_submitted',
        'similar_equipment_ids',
        'comparable_ai_profile_ids',
        'critical_matching_criteria',
        'allow_upgrades',
        'allow_downgrades',
        'downgrade_requires_approval',
        'equipment_key_comparison_notes',
        'assigned_product_id',
        'capabilities',
    ];

    protected $casts = [
        'current_status' => EquipmentCurrentStatus::class,
        'power_source_type' => EquipmentPowerSourceType::class,
        'key_starting_mechanism' => EquipmentKeyStartingMechanism::class,
        'volts' => 'array',
        'amps'  => 'array',
        // null = capabilities unknown (complaint list hides nothing);
        // a list (even empty) enforces complaint capability requirements
        'capabilities' => 'array',
        'similar_equipment_ids' => 'array',
        'comparable_ai_profile_ids' => 'array',
        'critical_matching_criteria' => 'array',
        'allow_upgrades' => 'boolean',
        'allow_downgrades' => 'boolean',
        'downgrade_requires_approval' => 'boolean',
    ];
    protected $appends = ['status_label', 'category_name', 'last_inspection'];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'EQP');
        });

        static::deleting(function ($model) {
            $model->documentImages->each(function ($document) {
                $document->delete();
            });
        });
    }

    // scopes

    public function scopeAvailable($query)
    {
        return $query->where('current_status', EquipmentCurrentStatus::Available);
    }

    public function scopeNotRented($query)
    {
        return $query->where('current_status', '!=', EquipmentCurrentStatus::Rented);
    }

    // relationships
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'current_order_id', 'id');
    }

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class, 'current_order_product_id', 'id');
    }

    public function orderProducts()
    {
        return $this->hasMany(OrderProduct::class, 'equipment_id')->withActiveOrder();
    }

    public function overdueOrderProducts()
    {
        return $this->hasMany(OrderProduct::class, 'equipment_id')
            ->withActiveOrder()
            ->whereNotNull('pickup_date')
            ->whereRaw(
                "TIMESTAMP(pickup_date, COALESCE(NULLIF(pickup_time, ''), '09:00:00')) < ?",
                [now()]
            )
            ->where('delivery_status', 'Completed')
            ->where('pickup_status', 'Pending')
            ->with(['order', 'order.customer']);
    }

    public function lastOrderProduct()
    {
        return $this->hasOne(OrderProduct::class, 'equipment_id')
            ->withActiveOrder()
            ->ofMany(['id' => 'max'], function ($query) {
                $query->where(function ($pending) {
                    $pending->where('delivery_status', 'Pending')
                        ->orWhere('pickup_status', 'Pending');
                });
            });
    }

    public function lastCompletedOrderProduct()
    {
        return $this->hasOne(OrderProduct::class, 'equipment_id')
            ->ofMany(['id' => 'max'], function ($query) {
                $query->where('pickup_status', 'Completed');
            });
    }

    public function nextAssignedOrderProduct()
    {
        return $this->hasOne(OrderProduct::class, 'equipment_id')
            ->ofMany(['delivery_date' => 'min'], function ($query) {
                $query->where('delivery_status', 'Pending')
                      ->whereNotNull('delivery_date')
                      ->where('delivery_date', '>=', now()->toDateString());
            });
    }

    /**
     * Next order for Equipment Inventory: earliest of hard assignment OR soft assignment.
     * Requires softAssignments.orderProduct.order and nextAssignedOrderProduct.order to be eager-loaded.
     */
    public function getNextInventoryOrderAttribute(): ?\App\Models\Orders\Order
    {
        $today = now()->toDateString();

        $hardDate  = $this->nextAssignedOrderProduct?->delivery_date;
        $hardOrder = $this->nextAssignedOrderProduct?->order;

        $softOrder = null;
        $softDate  = null;
        if ($this->relationLoaded('softAssignments')) {
            $earliest = $this->softAssignments
                ->filter(function ($sa) use ($today) {
                    return $sa->orderProduct
                        && $sa->orderProduct->delivery_status === 'Pending'
                        && $sa->orderProduct->delivery_date
                        && $sa->orderProduct->delivery_date >= $today;
                })
                ->sortBy('orderProduct.delivery_date')
                ->first();
            $softDate  = $earliest?->orderProduct?->delivery_date;
            $softOrder = $earliest?->order;
        }

        if ($hardOrder && $softOrder) {
            return $hardDate <= $softDate ? $hardOrder : $softOrder;
        }
        return $hardOrder ?? $softOrder;
    }


    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id', 'id');
    }

    public function assignedProduct()
    {
        return $this->belongsTo(\App\Models\ProductManagement\Product::class, 'assigned_product_id', 'id');
    }

    public function getCategoryNameAttribute()
    {
        return $this->productCategory?->title ?? '';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->current_status?->label() ?? 'Unknown';
    }
    public function checklistMaster()
    {
        return $this->belongsTo(ChecklistMaster::class, 'checklist_master_id');
    }

    public function serviceTemplate()
    {
        return $this->belongsTo(\App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplate::class, 'equipment_service_id');
    }

    public function partsList()
    {
        return $this->belongsTo(PartsList::class, 'parts_list_id', 'id');
    }

    public function specifications()
    {
        return $this->hasMany(EquipmentSpecification::class, 'equipment_id');
    }


    public function customerAdminTemplates()
    {
        return $this->hasOneThrough(
            CustomerAdminTemplate::class, // related
            ChecklistMaster::class,     // through
            'id',                       // through.firstKey      -> checklist_masters.id
            'id',                       // related.secondKey     -> customer_admin_templates.id
            'checklist_master_id',      // parent.localKey       -> equipment.checklist_master_id
            'customer_admin_template_id'// through.secondLocalKey-> checklist_masters.customer_admin_template_id
        );

    }

    public function linkWithTitle()
    {
        switch ($this->current_status) {
            case EquipmentCurrentStatus::Maintenance:
                return [
                    'link' => ($this?->unique_id) ? route('admin.checklist-management.equipment-management.show', $this?->unique_id) : '#',
                    'title' => ($this?->unique_id) ? 'Go to Equipment Management' : ''
                ];
                break;
            case EquipmentCurrentStatus::Damaged:
                return [
                    'link' => route('admin.checklist-management.equipment-management.index'),
                    'title' => 'Go to Rental Ready'
                ];
                break;
            default:
                return [
                    'link' => '#',
                    'title' => ''
                ];
                break;
        }
    }

    public function lastRentalReadyTemplate()
    {
        return $this->hasOne(EquipmentRentalReadyTemplate::class, 'equipment_id')->latestOfMany('id');
    }

    public function latestRentalReadyTemplate()
    {
        return $this->hasOne(EquipmentRentalReadyTemplate::class, 'equipment_id')
            ->latest('inspection_date')
            ->latest('inspection_time');
    }

    public function activeEquipmentRentalReadyTemplate()
    {
        return $this->hasOne(EquipmentRentalReadyTemplate::class, 'equipment_id')
            ->latest('id');
    }

    public function statusUpdatedByUser()
    {
        return $this->belongsTo(\App\Models\Iam\Personnel\User::class, 'current_status_updated_by', 'id');
    }

    public function getLastInspectionAttribute(): string
    {
        $inspectionDateTime = $this->current_status_changed_at
            ? \App\Helpers\CustomHelper::formatDateTime($this->current_status_changed_at)
            : '-';

        $updatedBy = $this->statusUpdatedByUser?->full_name ?? '-';
        return trim("{$updatedBy} - {$inspectionDateTime}");
        //return trim("  {$inspectionDateTime}");
    }

    public function softAssignments()
    {
        return $this->hasMany(EquipmentSoftAssign::class, 'equipment_id', 'id');
    }

    public function documentImages()
    {
        return $this->hasMany(EquipmentMedia::class, 'equipment_id')->with('media');
    }

    public function isHardAssigned()
    {
        return $this->current_order_product_id !== null;
    }

    public function equipmentLocation()
    {
        if ($this->status_label === 'Rented') {
            return $this->order?->customer_name ?? '-';
        }

        // if ($this->orderProduct?->checklistQuestions->isNotEmpty()) {
        //     return $this->status_label === 'Rented'
        //         ? $this->order?->customer_name ?? '-'
        //         : $this->store?->store_name ?? '-';
        // }


        if ($this->softAssignments && $this->softAssignments->isNotEmpty()) {
            return $this->softAssignment?->store?->store_name ?? '-';
        }


        return $this->store?->store_name ?? '-';
    }


}
