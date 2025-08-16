<?php

namespace App\Helpers;

// Models
use App\Models\ProductManagement\ProductCategory;

class CommonFrontDataHelper
{
    public static function setCommonFrontData()
    {
        $categoryTree = self::categoryTree();
        $contactUsSettings = self::contactUsSettings();

        $data = [
            'frontCategoryTree' => $categoryTree,
            'contactUsSettings' => $contactUsSettings,
        ];

        view()->share($data);
    }

    public static function contactUsSettings()
    {
        return ConfigurationHelper::getSettings('Contact Us Settings');
    }

    public static function categoryTree()
    {
        return ProductCategory::published()
            ->with([
                'childCategories' => function ($query) {
                    $query->published();
                },
            ])
            ->whereNull('parent_id')
            ->get();
    }
}
