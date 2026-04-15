<?php

namespace App\Models\ProductManagement;

use Illuminate\Database\Eloquent\Model;

class ProductCategoryChild extends Model
{
    protected $fillable = [
        'product_id',
        'product_category_id',
        'sub_category_id',
        'sort_order'
    ];

    protected $table = 'product_category_children';

    // Optional relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'sub_category_id');
    }

    public function scopeSortOrder($query)
    {
        return $query->orderBy('sort_order', 'ASC');
    }
}
