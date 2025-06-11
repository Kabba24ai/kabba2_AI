<?php

namespace App\Models\ProductManagement;

use Illuminate\Database\Eloquent\Model;

class ProductOptionChild extends Model
{
    protected $fillable = [
        'product_id',
        'product_option_id'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productOption()
    {
        return $this->belongsTo(ProductOption::class);
    }
}
