<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Helpers\ModelHelper;

class Part extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'part_name',
        'description',

        // Inventory fields
        'stock_level',
        'min_stock',
        'dni',
        'general_supply_item',
        'is_active',

        // Primary part details
        'primary_part_number',
        'primary_part_cost',
        'primary_part_supplier_id',

        // Alternative 1
        'alt_1_part_number',
        'alt_1_part_cost',
        'alt_1_part_supplier_id',

        // Alternative 2
        'alt_2_part_number',
        'alt_2_part_cost',
        'alt_2_part_supplier_id',
'assigned_equipments',
        'part_category_id',
    ];

    protected $casts = [
        'primary_part_cost'    => 'decimal:2',
        'alt_1_part_cost'      => 'decimal:2',
        'alt_2_part_cost'      => 'decimal:2',
        'dni'                  => 'boolean',
        'general_supply_item'  => 'boolean',
        'is_active'            => 'boolean',
    ];

protected $appends = ['assigned'];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                $model->unique_id = ModelHelper::generateUniqueID($model, 'PRT');
            }
        });
    }

public function templates()
{
    return $this->belongsToMany(
            PartsList::class,
            'lists_parts',
            'part_id',
            'parts_list_id'
        )
        ->withPivot('sort_order')
        ->orderBy('lists_parts.sort_order');
}

public function partsLists()
{
    return $this->belongsToMany(
        PartsList::class,
        'lists_parts',
        'part_id',
        'parts_list_id'
    )
    ->withPivot('sort_order')
    ->orderBy('lists_parts.sort_order');
}


    public function getStockStatusAttribute()
    {
        if ($this->dni) return 'dni';
        if ($this->stock_level === 0) return 'out-of-stock';
        if ($this->stock_level < $this->min_stock) return 'buy-now';
        return 'in-stock';
    }

    public function category()
    {
        return $this->belongsTo(PartCategory::class, 'part_category_id');
    }

    public function primarySupplier()
    {
        return $this->belongsTo(Supplier::class, 'primary_part_supplier_id', 'unique_id');
    }

    public function alt1Supplier()
    {
        return $this->belongsTo(Supplier::class, 'alt_1_part_supplier_id', 'unique_id');
    }

    public function alt2Supplier()
    {
        return $this->belongsTo(Supplier::class, 'alt_2_part_supplier_id', 'unique_id');
    }

    public function getAllSupplierNamesAttribute()
    {
        return collect([
            optional($this->primarySupplier)->name,
            optional($this->alt1Supplier)->name,
            optional($this->alt2Supplier)->name,
        ])->filter()->unique()->values()->implode(', ');
    }


    /**
 * Assign this part to a PartsList
 *
 * @param int $partsListId
 * @param int|null $sortOrder
 * @return void
 */
public function assignList($partsListId, $sortOrder = null)
{
    $this->partsLists()->syncWithoutDetaching([
        $partsListId => ['sort_order' => $sortOrder]
    ]);
}

/**
 * Check if this part is assigned to a specific PartsList
 *
 * @param int|null $partsListId — optional
 * @return bool
 */
public function isAssigned($partsListId = null)
{
    if ($partsListId) {
        return $this->partsLists()->where('parts_list_id', $partsListId)->exists();
    }

    // Check if assigned to ANY list
    return $this->partsLists()->exists();
}

/**
 * Always return TRUE/FALSE if this part is assigned to any PartsList
 */
public function getAssignedAttribute()
{
    return $this->partsLists()->exists();
}


public function getAssignedEquipmentNamesAttribute()
{
    return $this->partsLists
        ->flatMap(function ($list) {
            return Equipment::whereIn('id', (array) $list->selected_products)
                ->pluck('equipment_name');
        })
        ->unique()
        ->values();
}



}
