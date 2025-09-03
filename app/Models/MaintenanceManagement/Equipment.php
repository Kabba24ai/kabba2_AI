<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Helpers
use App\Helpers\ModelHelper;

// Equipments
use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Enums\Equipments\EquipmentPowerSourceType;
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplate;
use App\Models\ProductManagement\ProductCategory;

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
        'brand',
        'model',
        'model_year',
        'date_acquired',
        'purchase_cost',
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
        'equipment_notes',
        'current_status', // available, rented, maintenance, damaged
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'current_status' => EquipmentCurrentStatus::class,
        'power_source_type' => EquipmentPowerSourceType::class,
    ];
    protected $appends = ['status_label'];


    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'EQP');
        });
    }

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }


    public function getStatusLabelAttribute(): string
    {
        return $this->current_status?->label() ?? 'Unknown';
    }
    public function checklistMaster()
    {
        return $this->belongsTo(ChecklistMaster::class, 'checklist_master_id');
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
}
