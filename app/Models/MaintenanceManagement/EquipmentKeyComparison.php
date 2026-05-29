<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Model;

class EquipmentKeyComparison extends Model
{
    protected $table = 'equipment_key_comparisons';

    protected $fillable = [
        'equipment_id',
        'spec_label',
        'spec_value',
        'spec_unit',
        'is_manual_override',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_manual_override' => 'boolean',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    public function specification()
    {
        return $this->belongsTo(EquipmentSpecification::class, 'specification_id');
    }

    public static function saveKeyComparison(int $equipmentId, array $specificationIds): void
    {
        // Clear existing comparisons
        self::where('equipment_id', $equipmentId)->delete();

        // Add new ones with sort order
        foreach ($specificationIds as $order => $specId) {
            self::create([
                'equipment_id' => $equipmentId,
                'specification_id' => $specId,
                'sort_order' => $order,
            ]);
        }
    }

    public static function getKeyComparisons(int $equipmentId)
    {
        return self::where('equipment_id', $equipmentId)
            ->orderBy('sort_order')
            ->with('specification')
            ->get();
    }
}
