<?php

use App\Models\Configurations\Setting;
use Illuminate\Database\Migrations\Migration;

// Task Manager → Billing Operations → Primary Billing Admin designation.
// A single canonical settings row whose setting_value holds the users.id of
// the designated Primary Billing Admin (FK-in-a-setting, exactly like
// site_logo holds a Media id). Created empty (no designee) so
// PrimaryBillingAdminResolver returns null until an active employee is set.
// Also registered in SettingSeeder (Billing Settings group) so fresh installs
// get it and the per-type delete-unlisted sweep preserves it.
return new class extends Migration
{
    public function up(): void
    {
        Setting::firstOrCreate(
            [
                'setting_type' => 'Billing Settings',
                'setting_name' => 'primary_billing_admin_id',
            ],
            [
                'setting_title' => 'Primary Billing Admin',
                'value_type'    => 'text',
                'setting_value' => null,
                'sort_order'    => 1,
            ]
        );
    }

    public function down(): void
    {
        Setting::where('setting_type', 'Billing Settings')
            ->where('setting_name', 'primary_billing_admin_id')
            ->delete();
    }
};
