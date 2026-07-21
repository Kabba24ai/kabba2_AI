<?php

use App\Models\Configurations\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Renames the {{pickup_time}} merge field (introduced by
 * 2026_07_20_000002_add_pickup_time_merge_field_to_return_messages) to
 * {{return_time}}, matching the {{return_date}} naming already used by the
 * same templates — and the parallel {{delivery_date}}/{{delivery_time}}
 * naming now used on the delivery side. Only rewrites a setting_value that
 * still contains the exact old token, so an admin's already-customized
 * template text is left untouched.
 */
return new class extends Migration
{
    private const SETTING_NAMES = [
        'rental_return_day_before_truck_message',
        'rental_return_day_before_store_message',
        'rental_return_same_day_store_message',
    ];

    public function up(): void
    {
        $this->rename('{{pickup_time}}', '{{return_time}}');
    }

    public function down(): void
    {
        $this->rename('{{return_time}}', '{{pickup_time}}');
    }

    private function rename(string $from, string $to): void
    {
        foreach (self::SETTING_NAMES as $settingName) {
            $setting = Setting::where('setting_name', $settingName)->first();

            if ($setting && str_contains((string) $setting->setting_value, $from)) {
                $setting->setting_value = str_replace($from, $to, $setting->setting_value);
                $setting->save();
            }
        }
    }
};
