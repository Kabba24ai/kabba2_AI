<?php

namespace App\Helpers;
use Carbon\Carbon;
use App\Models\ProductManagement\ProductCategory;

class CustomHelper
{
    public static function formatCurrency($value)
    {
        if (is_null($value)) {
            return '-';
        }

        return config('app.currency.code') . number_format($value, 2);
    }
    public static function categoryTree()
    {
        return ProductCategory::with('childCategories')->whereNull('parent_id')->get();
    }
}
