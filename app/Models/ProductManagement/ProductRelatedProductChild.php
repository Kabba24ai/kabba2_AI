<?php

namespace App\Models\ProductManagement;

use Illuminate\Database\Eloquent\Model;

class ProductRelatedProductChild extends Model
{
    protected $fillable = [
        'product_id',
        'related_product_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function relatedProduct()
    {
        return $this->belongsTo(Product::class, 'related_product_id');
    }
}
