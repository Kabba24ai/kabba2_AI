<?php

namespace App\Models\Dispatch;

use App\Models\ProductManagement\ProductCategory;
use Illuminate\Database\Eloquent\Model;

class DispatchAiEquipmentRule extends Model
{
    protected $table = 'dispatch_ai_equipment_rules';

    protected $fillable = [
        'product_category_id',
        'min_trailer_capacity',
        'allowed_trailer_types',
        'allowed_truck_types',
        'cdl_required',
        'can_share_trailer',
        'must_haul_alone',
        'special_notes',
    ];

    protected function casts(): array
    {
        return [
            'allowed_trailer_types' => 'array',
            'allowed_truck_types'   => 'array',
            'cdl_required'          => 'boolean',
            'can_share_trailer'     => 'boolean',
            'must_haul_alone'       => 'boolean',
            'min_trailer_capacity'  => 'decimal:2',
        ];
    }

    public function productCategory(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }
}
