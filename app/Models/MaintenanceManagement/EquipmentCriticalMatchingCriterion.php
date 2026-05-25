<?php

namespace App\Models\MaintenanceManagement;

use App\Models\ProductManagement\ProductCategory;
use Illuminate\Database\Eloquent\Model;

class EquipmentCriticalMatchingCriterion extends Model
{
    protected $table = 'equipment_critical_matching_criteria';

    protected $fillable = [
        'product_category_id',
        'criteria_key',
        'name',
        'unit',
        'source_type',
        'default_weight',
        'upgrade_exceeds_value',
        'caution_if_change_value',
        'upgrade_is_below_value',
        'caution_if_below_value',
        'sort_order',
        'is_active',
        'is_key_criteria',
    ];

    protected $casts = [
        'default_weight' => 'integer',
        'upgrade_exceeds_value' => 'boolean',
        'caution_if_change_value' => 'boolean',
        'upgrade_is_below_value' => 'boolean',
        'caution_if_below_value' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_key_criteria' => 'boolean',
    ];

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id', 'id');
    }
}
