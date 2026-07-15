<?php

use App\Models\Configurations\Setting;
use Illuminate\Database\Migrations\Migration;

// Phase 2 refund workflow: the Credit Card Processing Fee (%) setting that
// backs the "Full Amount Less Card Processing Fee" refund shortcut. Stored
// as a whole percentage (e.g. 3.00, not 0.03), matching
// damage_waiver_percentage's convention in this same settings group.
return new class extends Migration
{
    public function up(): void
    {
        Setting::firstOrCreate(
            [
                'setting_type' => 'Product Settings',
                'setting_name' => 'credit_card_processing_fee',
            ],
            [
                'setting_title' => 'Credit Card Processing Fee',
                'value_type'    => 'number',
                'setting_value' => 0,
                'sort_order'    => 100,
            ]
        );
    }

    public function down(): void
    {
        Setting::where('setting_type', 'Product Settings')
            ->where('setting_name', 'credit_card_processing_fee')
            ->delete();
    }
};
