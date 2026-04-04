<?php

use App\Models\Configurations\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            [
                'value_type' => 'number',
                'setting_name' => 'small_daily_tire_insurance_fee',
                'setting_title' => 'Small Daily Tire Insurance Fee',
                'default_value' => 9,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_daily_tire_insurance_fee',
                'setting_title' => 'Medium Daily Tire Insurance Fee',
                'default_value' => 18,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_daily_tire_insurance_fee',
                'setting_title' => 'Large Daily Tire Insurance Fee',
                'default_value' => 24,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_daily_tire_insurance_fee',
                'setting_title' => 'X-Large Daily Tire Insurance Fee',
                'default_value' => 29,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_daily_tire_insurance_fee',
                'setting_title' => '2X-Large Daily Tire Insurance Fee',
                'default_value' => 34,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_daily_tire_insurance_fee',
                'setting_title' => 'Commercial Daily Tire Insurance Fee',
                'default_value' => 67,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'small_weekend_tire_insurance_fee',
                'setting_title' => 'Small Weekend Tire Insurance Fee',
                'default_value' => 12,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_weekend_tire_insurance_fee',
                'setting_title' => 'Medium Weekend Tire Insurance Fee',
                'default_value' => 24,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_weekend_tire_insurance_fee',
                'setting_title' => 'Large Weekend Tire Insurance Fee',
                'default_value' => 36,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_weekend_tire_insurance_fee',
                'setting_title' => 'X-Large Weekend Tire Insurance Fee',
                'default_value' => 39,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_weekend_tire_insurance_fee',
                'setting_title' => '2X-Large Weekend Tire Insurance Fee',
                'default_value' => 47,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_weekend_tire_insurance_fee',
                'setting_title' => 'Commercial Weekend Tire Insurance Fee',
                'default_value' => 97,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'small_weekly_tire_insurance_fee',
                'setting_title' => 'Small Weekly Tire Insurance Fee',
                'default_value' => 19,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_weekly_tire_insurance_fee',
                'setting_title' => 'Medium Weekly Tire Insurance Fee',
                'default_value' => 36,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_weekly_tire_insurance_fee',
                'setting_title' => 'Large Weekly Tire Insurance Fee',
                'default_value' => 48,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_weekly_tire_insurance_fee',
                'setting_title' => 'X-Large Weekly Tire Insurance Fee',
                'default_value' => 57,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_weekly_tire_insurance_fee',
                'setting_title' => '2X-Large Weekly Tire Insurance Fee',
                'default_value' => 67,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_weekly_tire_insurance_fee',
                'setting_title' => 'Commercial Weekly Tire Insurance Fee',
                'default_value' => 134,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'small_monthly_tire_insurance_fee',
                'setting_title' => 'Small Monthly Tire Insurance Fee',
                'default_value' => 27,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_monthly_tire_insurance_fee',
                'setting_title' => 'Medium Monthly Tire Insurance Fee',
                'default_value' => 54,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_monthly_tire_insurance_fee',
                'setting_title' => 'Large Monthly Tire Insurance Fee',
                'default_value' => 72,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_monthly_tire_insurance_fee',
                'setting_title' => 'X-Large Monthly Tire Insurance Fee',
                'default_value' => 57,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_monthly_tire_insurance_fee',
                'setting_title' => '2X-Large Monthly Tire Insurance Fee',
                'default_value' => 97,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_monthly_tire_insurance_fee',
                'setting_title' => 'Commercial Monthly Tire Insurance Fee',
                'default_value' => 197,
            ],
            [
                'value_type' => 'textarea',
                'setting_name' => 'tire_insurance_info',
                'setting_title' => 'Tire Insurance Message',
                'placeholder' => 'Enter message for tire insurance popup...',
                'default_value' => '<p>By removing Tire Insurance, you accept <strong>full financial responsibility</strong> for any tire damage during your rental (e.g., service call, labor, travel, and parts).</p><p><br>If a tire fails due to a defective component, it is covered automatically; however, this is uncommon.</p><p><br><em>*Covers 1 Tire service per rental period</em></p>',
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'tire_insurance_decline_label',
                'setting_title' => 'Decline Button Label',
                'placeholder' => 'Enter decline button text',
                'default_value' => "I'll Take The Risk",
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'tire_insurance_approve_label',
                'setting_title' => 'Accept Button Label',
                'placeholder' => 'Enter accept button text',
                'default_value' => 'Yes, Add Tire Insurance',
            ],
        ];

        $nextSortOrder = (int) Setting::where('setting_type', 'Product Settings')->max('sort_order') + 1;

        foreach ($settings as $setting) {
            $item = Setting::firstOrCreate(
                [
                    'setting_type' => 'Product Settings',
                    'setting_name' => $setting['setting_name'],
                ],
                [
                    'setting_title' => $setting['setting_title'],
                    'value_type' => $setting['value_type'],
                    'setting_options' => $setting['setting_options'] ?? null,
                    'sort_order' => $nextSortOrder++,
                    'placeholder' => $setting['placeholder'] ?? null,
                    'is_secure_field' => $setting['is_secure_field'] ?? 0,
                    'is_required' => $setting['is_required'] ?? 0,
                    'is_encrypted' => $setting['is_encrypted'] ?? 0,
                    'is_eye_toggle' => $setting['is_eye_toggle'] ?? 0,
                ],
            );

            if ($item->wasRecentlyCreated && array_key_exists('default_value', $setting)) {
                $item->setting_value = $setting['default_value'];
                $item->save();
            }
        }
    }

    public function down(): void
    {
        Setting::where('setting_type', 'Product Settings')
            ->whereIn('setting_name', [
                'small_daily_tire_insurance_fee',
                'medium_daily_tire_insurance_fee',
                'large_daily_tire_insurance_fee',
                'x_large_daily_tire_insurance_fee',
                '2x_large_daily_tire_insurance_fee',
                'commercial_daily_tire_insurance_fee',
                'small_weekend_tire_insurance_fee',
                'medium_weekend_tire_insurance_fee',
                'large_weekend_tire_insurance_fee',
                'x_large_weekend_tire_insurance_fee',
                '2x_large_weekend_tire_insurance_fee',
                'commercial_weekend_tire_insurance_fee',
                'small_weekly_tire_insurance_fee',
                'medium_weekly_tire_insurance_fee',
                'large_weekly_tire_insurance_fee',
                'x_large_weekly_tire_insurance_fee',
                '2x_large_weekly_tire_insurance_fee',
                'commercial_weekly_tire_insurance_fee',
                'small_monthly_tire_insurance_fee',
                'medium_monthly_tire_insurance_fee',
                'large_monthly_tire_insurance_fee',
                'x_large_monthly_tire_insurance_fee',
                '2x_large_monthly_tire_insurance_fee',
                'commercial_monthly_tire_insurance_fee',
                'tire_insurance_info',
                'tire_insurance_decline_label',
                'tire_insurance_approve_label',
            ])
            ->delete();
    }
};
