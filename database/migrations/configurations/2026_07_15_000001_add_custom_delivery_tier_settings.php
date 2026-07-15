<?php

use App\Models\Configurations\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Custom 1–4 delivery tiers (Phase 1 — Delivery Configuration Foundation).
     *
     * Adds the four Custom distance settings and the 24 per-size one-way rate
     * settings. Every value starts NULL: a blank Custom tier means "not
     * configured", so no production pricing is seeded here. firstOrCreate
     * keeps the migration idempotent and never touches existing Standard /
     * Extended rows.
     */
    public function up(): void
    {
        $settings = [];

        foreach ([1, 2, 3, 4] as $n) {
            $settings[] = [
                'setting_name'  => "custom_{$n}_delivery_range",
                'setting_title' => "Custom {$n} Delivery Range Up To",
                'placeholder'   => 'Enter numerical distance',
            ];
        }

        $sizes = [
            'small'      => 'Small',
            'medium'     => 'Medium',
            'large'      => 'Large',
            'x_large'    => 'X-Large',
            '2x_large'   => '2X-Large',
            'commercial' => 'Commercial',
        ];

        foreach ($sizes as $prefix => $label) {
            foreach ([1, 2, 3, 4] as $n) {
                $settings[] = [
                    'setting_name'  => "{$prefix}_custom_{$n}_delivery_fee",
                    'setting_title' => "{$label} Custom {$n} Delivery Fee",
                    'placeholder'   => null,
                ];
            }
        }

        foreach ($settings as $sortOrder => $setting) {
            Setting::firstOrCreate(
                [
                    'setting_type' => 'Product Settings',
                    'setting_name' => $setting['setting_name'],
                ],
                [
                    'setting_title' => $setting['setting_title'],
                    'value_type'    => 'number',
                    'setting_value' => null,
                    'placeholder'   => $setting['placeholder'],
                    'sort_order'    => $sortOrder + 1,
                ],
            );
        }
    }

    public function down(): void
    {
        // Intentionally left empty — removing the rows would destroy
        // administrator-entered Custom tier configuration.
    }
};
