<?php

namespace App\Helpers;

// Models

use App\Models\Configurations\Setting;
use App\Models\ProductManagement\ProductCategory;

class CommonFrontDataHelper
{
    public static function setCommonFrontData()
    {
        $categoryTree = self::categoryTree();
        $contactUsSettings = self::contactUsSettings();
        $brandingSettings = self::brandingSettings();

        $logo    = \App\Helpers\ConfigurationHelper::getBrandingLogo();
        $favicon = \App\Helpers\ConfigurationHelper::getBrandingFavicon();
        $hp      = app(\App\Services\Website\HomePageService::class)->getData();
        $data = [
            'frontCategoryTree' => $categoryTree,
            'contactUsSettings' => $contactUsSettings,
            'brandingSettings'  => $brandingSettings,
            'logo'              => $logo,
            'favicon'           => $favicon,
            'footerHp'          => $hp->footer,
        ];

        view()->share($data);
    }

    public static function contactUsSettings()
    {
        return ConfigurationHelper::getSettings('Contact Us Settings');
    }

    public static function brandingSettings()
    {
        return ConfigurationHelper::getSettings('Website Management Branding');
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
            ->orderBy('title', 'asc')
            ->get();
    }
}
