<?php

namespace App\Models\ProductManagement;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'product_option_id',
        'label',
        'daily',
        'weekend',
        'weekly',
        'monthly',
        'retail_price',
        'charged',
        'value',
        'comment',
        'accept_label',
        'decline_label',
        'sort_order',
    ];

    public function productOption()
    {
        return $this->belongsTo(ProductOption::class);
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'POPT-ITM');
        });
    }
}
