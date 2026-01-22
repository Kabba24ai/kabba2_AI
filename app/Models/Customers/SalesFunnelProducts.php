<?php

namespace App\Models\Customers;

use App\Models\ProductManagement\Product;
use Illuminate\Database\Eloquent\Model;

class SalesFunnelProducts extends Model
{
    protected $table = 'sales_funnel_products';

    protected $fillable = [
        'sales_funnel_id',
        'product_id',
    ];

    /** 🔗 Sales Funnel */
    public function salesFunnel()
    {
        return $this->belongsTo(SalesFunnel::class, 'sales_funnel_id');
    }

    /** 🔗 Product */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
