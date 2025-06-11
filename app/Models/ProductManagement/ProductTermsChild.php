<?php

namespace App\Models\ProductManagement;

use App\Models\TermsAndCondition\Terms;
use Illuminate\Database\Eloquent\Model;

class ProductTermsChild extends Model
{
    protected $fillable = [
        'product_id',
        'terms_and_condition_id'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function terms()
    {
        return $this->belongsTo(Terms::class);
    }
}
