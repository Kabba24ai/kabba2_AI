<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EquipmentAiCustomSpecValue extends Model
{
    use HasFactory;

    protected $table = 'equipment_ai_custom_spec_values';

    protected $fillable = [
        'custom_spec_id',
        'equipment_ai_profile_id',
        'spec_value',
        'confidence_score',
        'value_source',
        'source_reference',
        'ai_reason',
        'confirmed_by_user',
        'last_updated_by',
    ];

    protected $casts = [
        'confidence_score'  => 'float',
        'confirmed_by_user' => 'boolean',
    ];

    public function customSpec()
    {
        return $this->belongsTo(EquipmentAiCustomSpec::class, 'custom_spec_id');
    }

    public function profile()
    {
        return $this->belongsTo(EquipmentAiProfile::class, 'equipment_ai_profile_id');
    }
}
