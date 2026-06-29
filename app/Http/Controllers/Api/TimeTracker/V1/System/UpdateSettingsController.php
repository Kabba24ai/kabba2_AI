<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\System;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Api\BaseController;
use App\Models\Configurations\TimeTrackerSetting;
use App\Services\TimeTrackerAuditService;
use Illuminate\Http\Request;

class UpdateSettingsController extends BaseController
{

    /**
     * Update System Settings
     * @group Admin App
     */
    public function __invoke(Request $request)
    {
        $settings = TimeTrackerSetting::firstOrCreate(
            [],
            ['settings' => []]
        );

        // Capture current settings BEFORE the update
        $oldSettings = $settings->settings ?? [];
        $newSettings = $request->all();

        $settings->update([
            'settings' => $newSettings,
        ]);

        // Audit: one row per setting key whose value changed
        $categories = [
            'pay_increments'                   => 'payroll',
            'pay_period_type'                  => 'payroll',
            'pay_period_start_date'            => 'payroll',
            'minimum_lunch_duration_minutes'   => 'lunch',
            'default_lunch_duration_minutes'   => 'lunch',
            'auto_lunch_minutes'               => 'lunch',
            'auto_lunch_message'               => 'lunch',
            'limit_start_time_to_shift'        => 'scheduling',
            'limit_end_time_to_shift'          => 'scheduling',
            'daily_shifts'                     => 'scheduling',
            'first_clock_in_reminder_minutes'  => 'notifications',
            'second_clock_in_reminder_minutes' => 'notifications',
            'clock_in_message_1'               => 'notifications',
            'clock_in_message_2'               => 'notifications',
            'auto_clock_out_limit_minutes'     => 'auto_clock_out',
            'auto_clock_out_time'              => 'auto_clock_out',
            'auto_clock_out_message'           => 'auto_clock_out',
            'holidays'                         => 'holidays',
        ];

        // Changed or added keys
        foreach ($newSettings as $key => $newVal) {
            $oldVal  = $oldSettings[$key] ?? null;
            $oldNorm = self::normalizeSettingValue($oldVal);
            $newNorm = self::normalizeSettingValue($newVal);

            if ($oldNorm === $newNorm) {
                continue;
            }

            TimeTrackerAuditService::log([
                'store_id'    => null,
                'employee_id' => null,
                'entity_type' => 'system_settings',
                'entity_id'   => $settings->id,
                'action'      => 'settings_update',
                'field'       => $key,
                'old_value'   => $oldNorm,
                'new_value'   => $newNorm,
                'metadata'    => [
                    'request_source'   => 'admin_panel',
                    'setting_category' => $categories[$key] ?? 'general',
                ],
            ]);
        }

        // Keys present in old but removed from the new request (dropped settings)
        foreach (array_keys($oldSettings) as $key) {
            if (array_key_exists($key, $newSettings)) {
                continue;
            }
            $oldNorm = self::normalizeSettingValue($oldSettings[$key]);
            if ($oldNorm === null) {
                continue;
            }
            TimeTrackerAuditService::log([
                'store_id'    => null,
                'employee_id' => null,
                'entity_type' => 'system_settings',
                'entity_id'   => $settings->id,
                'action'      => 'settings_update',
                'field'       => $key,
                'old_value'   => $oldNorm,
                'new_value'   => null,
                'metadata'    => [
                    'request_source'   => 'admin_panel',
                    'setting_category' => $categories[$key] ?? 'general',
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'System settings updated successfully',
            'data' => $settings->settings,
        ]);
    }

    private static function normalizeSettingValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return (string) $value;
    }
}
