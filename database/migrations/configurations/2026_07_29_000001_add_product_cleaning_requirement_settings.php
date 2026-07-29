<?php

use App\Models\Configurations\Setting;
use Illuminate\Database\Migrations\Migration;

// Product Cleaning card: Std/Moderate/Extreme Clean Req. dollar amounts.
// firstOrCreate keeps this idempotent since the seeder isn't run on live.
return new class extends Migration
{
    public function up(): void
    {
        $sortOrder = (int) (Setting::where('setting_type', 'Product Settings')->max('sort_order')) + 1;

        $settings = [
            'std_clean_req' => 'Std Clean Req.',
            'moderate_clean_req' => 'Moderate Clean Req.',
            'extreme_clean_req' => 'Extreme Clean Req.',
        ];

        foreach ($settings as $settingName => $settingTitle) {
            Setting::firstOrCreate(
                [
                    'setting_type' => 'Product Settings',
                    'setting_name' => $settingName,
                ],
                [
                    'setting_title' => $settingTitle,
                    'value_type'    => 'number',
                    'setting_value' => 0,
                    'placeholder'   => 'Enter ' . strtolower($settingTitle),
                    'sort_order'    => $sortOrder++,
                ],
            );
        }
    }

    public function down(): void
    {
        Setting::where('setting_type', 'Product Settings')
            ->whereIn('setting_name', ['std_clean_req', 'moderate_clean_req', 'extreme_clean_req'])
            ->delete();
    }
};
