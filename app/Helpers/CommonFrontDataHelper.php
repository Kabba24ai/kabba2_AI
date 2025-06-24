<?php

namespace App\Helpers;

// Models
use App\Models\ProductManagement\ProductCategory;

class CommonFrontDataHelper
{
    public static function setCommonFrontData()
    {
        $categoryTree = self::categoryTree();

        $data = [
            'frontCategoryTree' => $categoryTree
        ];

        view()->share($data);
    }

    public static function categoryTree()
    {
        return ProductCategory::with('childCategories')->whereNull('parent_id')->get();
    }
}
