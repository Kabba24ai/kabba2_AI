<?php

use App\Models\Configurations\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::firstOrCreate(
            [
                'setting_type' => 'Time Zone Settings',
                'setting_name' => 'twilio_timezone',
            ],
            [
                'setting_title' => 'CRM / Twilio SMS Timezone',
                'value_type'    => 'options',
                'setting_value' => 'America/Chicago',
                'sort_order'    => 1,
            ]
        );
    }

    public function down(): void
    {
        Setting::where('setting_type', 'Time Zone Settings')
            ->where('setting_name', 'twilio_timezone')
            ->delete();
    }
};
