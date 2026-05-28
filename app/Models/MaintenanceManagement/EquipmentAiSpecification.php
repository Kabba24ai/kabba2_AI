<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EquipmentAiSpecification extends Model
{
    use HasFactory;

    protected $table = 'equipment_ai_specifications';

    protected $fillable = [
        'equipment_ai_profile_id',
        'spec_key',
        'spec_label',
        'spec_value',
        'spec_unit',
        'confidence_score',
        'source',
    ];

    protected $casts = [
        'confidence_score' => 'float',
    ];

    /* ------------------------------------------------------------------
     | Relationships
     ------------------------------------------------------------------ */

    public function profile()
    {
        return $this->belongsTo(EquipmentAiProfile::class, 'equipment_ai_profile_id');
    }
}
