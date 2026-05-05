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
        'default_weight',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'default_weight' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id', 'id');
    }
}
