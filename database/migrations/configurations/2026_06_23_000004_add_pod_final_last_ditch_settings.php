<?php

use App\Models\Configurations\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $sortOrder = 500;

        $settings = [
            [
                'setting_name'  => 'pod_final_reminder_truck_message',
                'setting_title' => 'POD Final Rental Reminder (9 AM) — Truck Delivery Message',
                'value_type'    => 'text',
                'setting_value' => "Rent 'n King: We're ready to send your delivery, but payment has not been received yet.\n\nYour equipment cannot be loaded or dispatched until payment is completed.\n\nPay now:\n{{payment_link}}\n\nOr call/text us immediately at (615) 815-6734 and we'll process your payment over the phone.\n\nIf you no longer want this equipment rental, please reply to this text and let us know ~ Thanks",
                'sort_order'    => $sortOrder++,
            ],
            [
                'setting_name'  => 'pod_final_reminder_truck_message_enabled',
                'setting_title' => 'Enable POD Final Rental Reminder (9 AM) — Truck Delivery',
                'value_type'    => 'checkbox',
                'setting_value' => '1',
                'sort_order'    => $sortOrder++,
            ],
            [
                'setting_name'  => 'pod_final_reminder_store_message',
                'setting_title' => 'POD Final Rental Reminder (9 AM) — Store Pickup Message',
                'value_type'    => 'text',
                'setting_value' => "Rent 'n King: Your equipment is ready for pickup, but payment has not been received yet.\n\nYour equipment cannot be released until payment is completed.\n\nPay now:\n{{payment_link}}\n\nOr call/text us immediately at (615) 815-6734 and we'll process your payment over the phone.\n\nIf you no longer want this equipment rental, please reply to this text and let us know ~ Thanks",
                'sort_order'    => $sortOrder++,
            ],
            [
                'setting_name'  => 'pod_final_reminder_store_message_enabled',
                'setting_title' => 'Enable POD Final Rental Reminder (9 AM) — Store Pickup',
                'value_type'    => 'checkbox',
                'setting_value' => '1',
                'sort_order'    => $sortOrder++,
            ],
            [
                'setting_name'  => 'pod_last_ditch_truck_message',
                'setting_title' => 'POD Last Ditch Recovery (4 PM) — Truck Delivery Message',
                'value_type'    => 'text',
                'setting_value' => "Rent 'n King: We understand things happen and schedules can change.\n\nIf you still need your rental equipment, simply complete payment using the link below and you can update your rental date on the reservation page.\n\nPay here:\n{{payment_link}}\n\nPrefer to pay by phone? Call or text us at (615) 815-6734 and we'll be happy to help.\n\nIf you no longer need this rental, simply reply to this text and let us know.\n\nThank you,\nRent 'n King",
                'sort_order'    => $sortOrder++,
            ],
            [
                'setting_name'  => 'pod_last_ditch_truck_message_enabled',
                'setting_title' => 'Enable POD Last Ditch Recovery (4 PM) — Truck Delivery',
                'value_type'    => 'checkbox',
                'setting_value' => '1',
                'sort_order'    => $sortOrder++,
            ],
            [
                'setting_name'  => 'pod_last_ditch_store_message',
                'setting_title' => 'POD Last Ditch Recovery (4 PM) — Store Pickup Message',
                'value_type'    => 'text',
                'setting_value' => "Rent 'n King: We understand things happen and schedules can change.\n\nIf you still need your rental equipment, simply complete payment using the link below and you can update your rental date on the reservation page.\n\nPay here:\n{{payment_link}}\n\nPrefer to pay by phone? Call or text us at (615) 815-6734 and we'll be happy to help.\n\nIf you no longer need this rental, simply reply to this text and let us know.\n\nThank you,\nRent 'n King",
                'sort_order'    => $sortOrder++,
            ],
            [
                'setting_name'  => 'pod_last_ditch_store_message_enabled',
                'setting_title' => 'Enable POD Last Ditch Recovery (4 PM) — Store Pickup',
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
                'pod_final_reminder_truck_message',
                'pod_final_reminder_truck_message_enabled',
                'pod_final_reminder_store_message',
                'pod_final_reminder_store_message_enabled',
                'pod_last_ditch_truck_message',
                'pod_last_ditch_truck_message_enabled',
                'pod_last_ditch_store_message',
                'pod_last_ditch_store_message_enabled',
            ])->delete();
    }
};
