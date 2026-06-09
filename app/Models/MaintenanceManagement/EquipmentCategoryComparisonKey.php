<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\ProductManagement\ProductCategory;

class EquipmentCategoryComparisonKey extends Model
{
    use HasFactory;

    protected $table = 'equipment_category_comparison_keys';

    protected $fillable = [
        'category_id',
        'spec_key',
        'display_label',
        'importance_level',
        'comparison_type',
        'sort_order',
        'is_required',
        'upgrade_exceeds_value',
        'caution_if_change_value',
        'upgrade_is_below_value',
        'caution_if_below_value',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'upgrade_exceeds_value' => 'boolean',
        'caution_if_change_value' => 'boolean',
        'upgrade_is_below_value' => 'boolean',
        'caution_if_below_value' => 'boolean',
        'sort_order'  => 'integer',
    ];

    /* ------------------------------------------------------------------
     | Relationships
     ------------------------------------------------------------------ */

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /* ------------------------------------------------------------------
     | Helpers
     ------------------------------------------------------------------ */

    public function importanceBadgeClass(): string
    {
        return match ($this->importance_level) {
            'critical' => 'bg-red-100 text-red-700',
            'high'     => 'bg-orange-100 text-orange-700',
            'medium'   => 'bg-blue-100 text-blue-700',
            'low'      => 'bg-gray-100 text-gray-500',
            default    => 'bg-gray-100 text-gray-500',
        };
    }

    public function comparisonTypeLabel(): string
    {
        return match ($this->comparison_type) {
            'higher_is_better'   => 'Higher is Better',
            'lower_is_better'    => 'Lower is Better',
            'must_match'         => 'Must Match',
            'range_acceptable'   => 'Range Acceptable',
            'informational_only' => 'Info Only',
            default              => ucfirst($this->comparison_type),
        };
    }
}
