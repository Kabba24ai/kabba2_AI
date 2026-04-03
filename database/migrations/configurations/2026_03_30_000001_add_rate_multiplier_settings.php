<?php

use App\Models\Configurations\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $multipliers = [
            [
                'setting_type'  => 'Price Rate Multiplier Settings',
                'setting_name'  => 'weekend_multiplier',
                'setting_title' => 'Weekend Rate Multiplier',
                'value_type'    => 'number',
                'setting_value' => null,
                'sort_order'    => 1,
            ],
            [
                'setting_type'  => 'Price Rate Multiplier Settings',
                'setting_name'  => 'weekly_multiplier',
                'setting_title' => 'Weekly Rate Multiplier',
                'value_type'    => 'number',
                'setting_value' => null,
                'sort_order'    => 2,
            ],
            [
                'setting_type'  => 'Price Rate Multiplier Settings',
                'setting_name'  => 'monthly_multiplier',
                'setting_title' => 'Monthly Rate Multiplier',
                'value_type'    => 'number',
                'setting_value' => null,
                'sort_order'    => 3,
            ],
        ];

        foreach ($multipliers as $setting) {
            $setting_item = Setting::firstOrCreate(
                [
                    'setting_type' => $setting['setting_type'],
                    'setting_name' => $setting['setting_name'],
                ],
                [
                    'setting_title' => $setting['setting_title'],
                    'value_type' => $setting['value_type'],
                    'setting_options' => $setting['setting_options'] ?? null,
                    'sort_order' => $setting['sort_order'] ?? 0,
                    'placeholder' => $setting['placeholder'] ?? null,
                    'is_secure_field' => $setting['is_secure_field'] ?? 0,
                    'is_required' => $setting['is_required'] ?? 0,
                    'is_encrypted' => $setting['is_encrypted'] ?? 0,
                    'is_eye_toggle' => $setting['is_eye_toggle'] ?? 0,
                ],
            );
        }
    }

    public function down(): void
    {

    }
};
