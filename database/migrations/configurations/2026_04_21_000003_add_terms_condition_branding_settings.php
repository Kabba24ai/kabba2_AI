<?php

use App\Models\Configurations\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settings = [
            [
                'setting_name' => 'terms_condition_text_1',
                'setting_title' => 'Terms & Conditions Text 1',
                'setting_value' => 'Rental Agreement',
                'sort_order' => 11,
            ],
            [
                'setting_name' => 'terms_condition_text_2',
                'setting_title' => 'Terms & Conditions Text 2',
                'setting_value' => null,
                'sort_order' => 12,
            ],
            [
                'setting_name' => 'terms_condition_text_3',
                'setting_title' => 'Terms & Conditions Text 3',
                'setting_value' => null,
                'sort_order' => 13,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['setting_name' => $setting['setting_name']],
                [
                    'setting_type' => 'Website Management Branding',
                    'setting_title' => $setting['setting_title'],
                    'value_type' => 'text',
                    'setting_value' => $setting['setting_value'],
                    'sort_order' => $setting['sort_order'],
                    'placeholder' => $setting['setting_title'],
                    'is_secure_field' => 0,
                    'is_eye_toggle' => 0,
                    'is_required' => 0,
                    'is_encrypted' => 0,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::query()
            ->where('setting_type', 'Website Management Branding')
            ->whereIn('setting_name', [
                'terms_condition_text_1',
                'terms_condition_text_2',
                'terms_condition_text_3',
            ])
            ->delete();
    }
};
