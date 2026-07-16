<?php

use App\Models\Configurations\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Smart Rental Price Rounding settings.
     *
     * Lives in 'Price Rate Multiplier Settings' so the existing Product
     * Settings save path and the product form's multiplier payload pick the
     * values up without new plumbing. Seeded with the initially requested
     * configuration (endings 4 and 7, $10 hundred-entry threshold); admins
     * can change or clear them on the Configurations page. firstOrCreate
     * keeps the migration idempotent and never overwrites edited values.
     */
    public function up(): void
    {
        $settings = [
            [
                'setting_name' => 'allowed_price_endings',
                'setting_title' => 'Allowed Price Endings',
                'value_type' => 'text',
                'setting_value' => '4,7',
                'placeholder' => 'e.g. 4,7',
            ],
            [
                'setting_name' => 'hundred_entry_threshold',
                'setting_title' => 'Hundred-Entry Threshold',
                'value_type' => 'number',
                'setting_value' => '10',
                'placeholder' => 'Whole dollars, e.g. 10',
            ],
        ];

        foreach ($settings as $sortOrder => $setting) {
            Setting::firstOrCreate(
                [
                    'setting_type' => 'Price Rate Multiplier Settings',
                    'setting_name' => $setting['setting_name'],
                ],
                [
                    'setting_title' => $setting['setting_title'],
                    'value_type' => $setting['value_type'],
                    'setting_value' => $setting['setting_value'],
                    'placeholder' => $setting['placeholder'],
                    'sort_order' => 90 + $sortOrder,
                ],
            );
        }
    }

    public function down(): void
    {
        // Intentionally left empty — removing the rows would destroy
        // administrator-entered rounding configuration.
    }
};
