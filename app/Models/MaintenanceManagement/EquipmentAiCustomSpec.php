<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EquipmentAiCustomSpec extends Model
{
    use HasFactory;

    protected $table = 'equipment_ai_custom_specs';

    protected $fillable = [
        'category_id',
        'spec_key',
        'spec_label',
        'description',
        'value_type',
        'allowed_values',
        'is_key_comparison',
        'is_ignored',
    ];

    protected $casts = [
        'allowed_values'    => 'array',
        'is_key_comparison' => 'boolean',
        'is_ignored'        => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(\App\Models\ProductManagement\ProductCategory::class, 'category_id');
    }

    public function values()
    {
        return $this->hasMany(EquipmentAiCustomSpecValue::class, 'custom_spec_id');
    }
}
