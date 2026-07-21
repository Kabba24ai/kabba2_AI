<?php

use App\Models\Configurations\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * SMS_AUTOMATION_AUDIT.md — pickup/delivery-time correction, delivery side.
 * The two rental delivery SMS templates below hardcode "9:00 AM" prose with
 * no awareness of the order's actual scheduled delivery time (wrong for
 * Weekend Special and any other non-standard schedule).
 * SendDeliveryDayBeforeRentalReminderJob / SendDeliverySameDayRentalReminderJob
 * now substitute a real {{delivery_time}} merge field from
 * OrderProduct.delivery_time — this migration updates each setting_value
 * only if it still contains the exact original hardcoded phrase, so an
 * admin's already-customized template text is left untouched.
 *
 * Matches the live, already-customized phrasing on this environment
 * ("starts at 9:00 AM tomorrow" / "starts today at 9:00 AM"), not the
 * (different-wording) seeder default — both are covered by their own
 * REPLACEMENTS entry.
 */
return new class extends Migration
{
    private const REPLACEMENTS = [
        'rental_delivery_day_before_store_message' => [
            ['from' => 'starts at 9:00 AM tomorrow', 'to' => 'starts at {{delivery_time}} tomorrow'],
        ],
        'rental_delivery_same_day_store_message' => [
            ['from' => 'starts today at 9:00 AM', 'to' => 'starts today at {{delivery_time}}'],
        ],
    ];

    public function up(): void
    {
        foreach (self::REPLACEMENTS as $settingName => $variants) {
            $setting = Setting::where('setting_name', $settingName)->first();

            if (!$setting) {
                continue;
            }

            foreach ($variants as $variant) {
                if (str_contains((string) $setting->setting_value, $variant['from'])) {
                    $setting->setting_value = str_replace($variant['from'], $variant['to'], $setting->setting_value);
                    $setting->save();
                    break;
                }
            }
        }
    }

    public function down(): void
    {
        foreach (self::REPLACEMENTS as $settingName => $variants) {
            $setting = Setting::where('setting_name', $settingName)->first();

            if (!$setting) {
                continue;
            }

            foreach ($variants as $variant) {
                if (str_contains((string) $setting->setting_value, $variant['to'])) {
                    $setting->setting_value = str_replace($variant['to'], $variant['from'], $setting->setting_value);
                    $setting->save();
                    break;
                }
            }
        }
    }
};
