<?php

namespace App\Models\ProductManagement;

use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductEquipmentAssignmentPathItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_path_id',
        'equipment_id',
        'sort_order',
    ];

    public function path()
    {
        return $this->belongsTo(ProductEquipmentAssignmentPath::class, 'assignment_path_id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }
}
