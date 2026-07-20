<?php

use App\Models\Configurations\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * SMS_AUTOMATION_AUDIT.md — pickup-time correction. The three rental return
 * SMS templates below hardcoded "9:00 AM" prose with no awareness of the
 * order's actual scheduled pickup time (wrong for Weekend Special and any
 * other non-standard schedule). SendReturnDayBeforeRentalReminderJob /
 * SendReturnSameDayRentalReminderJob now substitute a real {{pickup_time}}
 * merge field from OrderProduct.pickup_time — this migration updates each
 * setting_value only if it still contains the exact original hardcoded
 * phrase, so an admin's already-customized template text is left untouched.
 */
return new class extends Migration
{
    private const REPLACEMENTS = [
        'rental_return_day_before_truck_message' => [
            'from' => 'tomorrow by 9:00 AM',
            'to' => 'tomorrow by {{pickup_time}}',
        ],
        'rental_return_day_before_store_message' => [
            'from' => 'tomorrow by 9:00 AM',
            'to' => 'tomorrow by {{pickup_time}}',
        ],
        'rental_return_same_day_store_message' => [
            'from' => 'today by 9:00 AM',
            'to' => 'today by {{pickup_time}}',
        ],
    ];

    public function up(): void
    {
        foreach (self::REPLACEMENTS as $settingName => $replacement) {
            $setting = Setting::where('setting_name', $settingName)->first();

            if ($setting && str_contains((string) $setting->setting_value, $replacement['from'])) {
                $setting->setting_value = str_replace($replacement['from'], $replacement['to'], $setting->setting_value);
                $setting->save();
            }
        }
    }

    public function down(): void
    {
        foreach (self::REPLACEMENTS as $settingName => $replacement) {
            $setting = Setting::where('setting_name', $settingName)->first();

            if ($setting && str_contains((string) $setting->setting_value, $replacement['to'])) {
                $setting->setting_value = str_replace($replacement['to'], $replacement['from'], $setting->setting_value);
                $setting->save();
            }
        }
    }
};
