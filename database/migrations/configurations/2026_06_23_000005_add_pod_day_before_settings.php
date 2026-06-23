<?php

use App\Models\Configurations\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $sortOrder = 450;

        $settings = [
            [
                'setting_name'  => 'pod_day_before_truck_message',
                'setting_title' => 'POD Day Before Delivery (3 PM) — Truck Delivery Message',
                'value_type'    => 'text',
                'setting_value' => "Rent 'n King: Your rental delivery is scheduled for {{delivery_date}}. This is a POD order — equipment cannot be loaded or dispatched until payment is completed.\n\nPay now: {{payment_link}}\n\nOr call/text us at (615) 815-6734 and we'll process your payment over the phone.",
                'sort_order'    => $sortOrder++,
            ],
            [
                'setting_name'  => 'pod_day_before_truck_message_enabled',
                'setting_title' => 'Enable POD Day Before Delivery (3 PM) — Truck Delivery',
                'value_type'    => 'checkbox',
                'setting_value' => '1',
                'sort_order'    => $sortOrder++,
            ],
            [
                'setting_name'  => 'pod_day_before_store_message',
                'setting_title' => 'POD Day Before Delivery (3 PM) — Store Pickup Message',
                'value_type'    => 'text',
                'setting_value' => "Rent 'n King: Your rental pickup at our {{store_name}} location is scheduled for {{delivery_date}}. This is a POD order — equipment cannot be released until payment is completed.\n\nPay now: {{payment_link}}\n\nOr call/text us at (615) 815-6734 and we'll process your payment over the phone.",
                'sort_order'    => $sortOrder++,
            ],
            [
                'setting_name'  => 'pod_day_before_store_message_enabled',
                'setting_title' => 'Enable POD Day Before Delivery (3 PM) — Store Pickup',
                'value_type'    => 'checkbox',
                'setting_value' => '1',
                'sort_order'    => $sortOrder++,
            ],
        ];

        foreach ($settings as $setting) {
            $item = Setting::firstOrCreate(
                [
                    'setting_type' => 'Default Sales Funnel Settings',
                    'setting_name' => $setting['setting_name'],
                ],
                [
                    'setting_title' => $setting['setting_title'],
                    'value_type'    => $setting['value_type'],
                    'sort_order'    => $setting['sort_order'],
                ],
            );

            if ($item->wasRecentlyCreated) {
                $item->setting_value = $setting['setting_value'];
                $item->save();
            }
        }
    }

    public function down(): void
    {
        Setting::where('setting_type', 'Default Sales Funnel Settings')
            ->whereIn('setting_name', [
                'pod_day_before_truck_message',
                'pod_day_before_truck_message_enabled',
                'pod_day_before_store_message',
                'pod_day_before_store_message_enabled',
            ])->delete();
    }
};
