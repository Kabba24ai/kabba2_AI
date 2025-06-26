<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Part extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'part_name',
        'category',
        'equipment_name',
        'equipment_id',
        'part_number',
        'supplier',
        'unit_cost',
        'stock_level',
        'min_stock',
        'dni',
        'description',
        'part_number_alt_1',
        'cost_alt_1',
        'supplier_alt_1',
        'part_number_alt_2',
        'cost_alt_2',
        'supplier_alt_2',
        'is_active'
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'cost_alt_1' => 'decimal:2',
        'cost_alt_2' => 'decimal:2',
        'dni' => 'boolean',
        'is_active' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                $model->unique_id = Str::uuid();
            }
        });
    }

    public function templates()
    {
        return $this->belongsToMany(Template::class, 'template_parts')
                    ->withPivot('sort_order')
                    ->orderBy('pivot_sort_order');
    }

    public function getStockStatusAttribute()
    {
        if ($this->dni) return 'dni';
        if ($this->stock_level === 0) return 'out-of-stock';
        if ($this->stock_level < $this->min_stock) return 'buy-now';
        return 'in-stock';
    }
}
