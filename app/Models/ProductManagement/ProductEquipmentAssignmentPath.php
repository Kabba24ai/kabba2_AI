<?php

namespace App\Models\ProductManagement;

use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductEquipmentAssignmentPath extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'path_type',
        'product_category_id',
        'sort_order',
    ];

    public function assignment()
    {
        return $this->belongsTo(ProductEquipmentAssignment::class, 'assignment_id');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function items()
    {
        return $this->hasMany(ProductEquipmentAssignmentPathItem::class, 'assignment_path_id')
            ->orderBy('sort_order');
    }

    public function equipment()
    {
        return $this->belongsToMany(
            Equipment::class,
            'product_equipment_assignment_path_items',
            'assignment_path_id',
            'equipment_id'
        )
            ->withPivot('sort_order')
            ->orderBy('pivot_sort_order');
    }
}
