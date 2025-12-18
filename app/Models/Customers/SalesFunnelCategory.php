<?php

namespace App\Models\Customers;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;

class SalesFunnelCategory extends Model
{
    protected $table = 'sales_funnel_categories';

    protected $fillable = [
        'unique_id',
        'category_name',
        'description',
        'color_code',
    ];

    // Auto-generate UUID when creating
    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'SF-CAT');
        });
    }

}
