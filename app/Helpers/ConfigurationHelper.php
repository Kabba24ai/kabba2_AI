<?php

namespace App\Helpers;

use App\Models\Configurations\Setting;

class ConfigurationHelper
{
    public static function getSettings($setting_type = null, $key = null)
    {
        // Build base query
        $query = Setting::query();

        if (!is_null($key)) {
            $query->where('setting_name', $key);
        }
        if (!is_null($setting_type)) {
            $query->where('setting_type', $setting_type);
        }

        // Fetch settings
        $settings = $query->get();

        $result = [];

        foreach ($settings as $setting) {
            $value = $setting->getSetting();
            $result[$setting->setting_name] = $value;
            $result[$setting->setting_name . '_formatted'] = $value > 0 ? number_format((float)$value) : '';
        }

        if (!is_null($key)) {
            return $result[$key] ?? null;
        }

        return $result;
    }
}
