<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Helpers\ModelHelper;

use App\Models\Iam\Personnel\User;
use App\Models\ProductManagement\ProductCategory;

class PartsList extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'name',
        'category_id',
        'description',
        'is_active',
        'created_by',
        'selected_products',

    ];

    protected $casts = [
        'is_active' => 'boolean',
        'selected_products' => 'array',

    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                $model->unique_id = ModelHelper::generateUniqueID($model, 'PLS');
            }
        });


        static::deleting(function ($template) {
            // Delete pivot records in template_parts table
            $template->parts()->detach();
        });

    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }



        public function getProductDetailsAttribute()
        {
            $productIds = (array) $this->selected_products;

            if (empty($productIds)) {
                return [];
            }

            return \App\Models\MaintenanceManagement\Equipment::whereIn('id', $productIds)
                ->get(['id', 'equipment_name', 'equipment_id'])
                ->map(function ($product) {
                    return [
                        'name' => $product->equipment_name,
                        'equipment_id' => $product->equipment_id,
                    ];
                })
                ->toArray();
        }


    // In PartTemplate model
    public function parts()
    {
        return $this->belongsToMany(Part::class, 'lists_parts', 'parts_list_id', 'part_id')
            ->withPivot('sort_order')
            ->orderBy('sort_order');
    }
}
