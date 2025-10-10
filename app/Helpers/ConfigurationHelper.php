<?php

namespace App\Helpers;

use App\Models\Configurations\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

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


    /**
     *  Safely decrypt any given value.
     * If it's not actually encrypted or invalid, it returns the value as-is.
     */
    public static function safeDecrypt(?string $value): ?string
    {
        if (empty($value)) {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            // Not encrypted or invalid payload
            return $value;
        } catch (\Exception $e) {
            // Any other errors (like wrong key, null)
            return $value;
        }
    }

    /**
     *  Get and automatically decrypt a setting from DB.
     *
     * Example:
     *   ConfigurationHelper::getDecryptedSetting('Mail Send Settings', 'mail_password');
     */
    public static function getDecryptedSetting(string $setting_type, string $key): ?string
    {
        $value = self::getSettings($setting_type, $key);
        return self::safeDecrypt($value);
    }
}
