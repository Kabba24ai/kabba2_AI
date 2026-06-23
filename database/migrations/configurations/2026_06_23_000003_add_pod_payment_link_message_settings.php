<?php

use App\Models\Configurations\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $sortOrder = 400;

        $settings = [
            [
                'setting_name'  => 'pod_payment_link_truck_message',
                'setting_title' => 'POD Payment Link — Truck Delivery Message',
                'value_type'    => 'text',
                'setting_value' => "Rent 'n King: Here's your secure payment link to confirm your rental. Our truck will be headed your way once payment is received: {{payment_link}}",
                'sort_order'    => $sortOrder++,
            ],
            [
                'setting_name'  => 'pod_payment_link_truck_message_enabled',
                'setting_title' => 'Enable POD Payment Link — Truck Delivery Message',
                'value_type'    => 'checkbox',
                'setting_value' => '1',
                'sort_order'    => $sortOrder++,
            ],
            [
                'setting_name'  => 'pod_payment_link_store_message',
                'setting_title' => 'POD Payment Link — Store Pickup Message',
                'value_type'    => 'text',
                'setting_value' => "Rent 'n King: Here's your secure payment link to confirm your rental at our {{store_name}} location. Equipment cannot be released until payment is received: {{payment_link}}",
                'sort_order'    => $sortOrder++,
            ],
            [
                'setting_name'  => 'pod_payment_link_store_message_enabled',
                'setting_title' => 'Enable POD Payment Link — Store Pickup Message',
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
                'pod_payment_link_truck_message',
                'pod_payment_link_truck_message_enabled',
                'pod_payment_link_store_message',
                'pod_payment_link_store_message_enabled',
            ])->delete();
    }
};
