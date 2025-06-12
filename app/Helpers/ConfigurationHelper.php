<?php

namespace App\Helpers;

use App\Models\Configurations\Setting;

class ConfigurationHelper
{
    public static function getSettings($setting_type = null, $key = null)
    {
        // If key is provided, fetch just that setting (with optional type)
        if (!is_null($key)) {
            $query = Setting::where('setting_name', $key);
            if (!is_null($setting_type)) {
                $query->where('setting_type', $setting_type);
            }
            $setting_item = $query->first();
            if ($setting_item) {
                return [
                    $setting_item->setting_name => $setting_item->getSetting(),
                    $setting_item->setting_name . '_formatted' => $setting_item->getSetting() > 0 ? number_format(floatval($setting_item->getSetting())) : '',
                ];
            }
            return []; // Not found
        }

        // Else: fetch all (optionally by type)
        $setting_list = !is_null($setting_type) ? Setting::where('setting_type', $setting_type)->get() : Setting::get();

        $settingArr = [];
        if ($setting_list && count($setting_list) > 0) {
            foreach ($setting_list as $setting_item) {
                $settingArr[$setting_item->setting_name] = $setting_item->getSetting();
                $settingArr[$setting_item->setting_name . '_formatted'] = $setting_item->getSetting() > 0 ? number_format(floatval($setting_item->getSetting())) : '';
            }
        }
        return $settingArr;
    }
}
