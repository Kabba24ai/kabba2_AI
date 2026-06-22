<?php

use App\Models\Configurations\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $sortOrder = 300;

        $settings = [
            [
                'setting_name'  => 'pod_payment_reminder_1_message',
                'setting_title' => 'POD Payment Reminder #1 Message',
                'value_type'    => 'text',
                'setting_value' => "Rent 'n King: Your rental order is not reserved until payment is complete. Pay here to secure your order: {{payment_link}}",
                'sort_order'    => $sortOrder++,
            ],
            [
                'setting_name'  => 'pod_payment_reminder_1_enabled',
                'setting_title' => 'Enable POD Payment Reminder #1',
                'value_type'    => 'checkbox',
                'setting_value' => '1',
                'sort_order'    => $sortOrder++,
            ],
            [
                'setting_name'  => 'pod_payment_reminder_2_message',
                'setting_title' => 'POD Payment Reminder #2 Message',
                'value_type'    => 'text',
                'setting_value' => "Rent 'n King reminder: Your rental starts today, but payment is still needed before your order is guaranteed. Pay here: {{payment_link}}",
                'sort_order'    => $sortOrder++,
            ],
            [
                'setting_name'  => 'pod_payment_reminder_2_enabled',
                'setting_title' => 'Enable POD Payment Reminder #2',
                'value_type'    => 'checkbox',
                'setting_value' => '1',
                'sort_order'    => $sortOrder++,
            ],
            [
                'setting_name'  => 'pod_payment_reminder_3_message',
                'setting_title' => 'POD Payment Reminder #3 Message (Last Chance)',
                'value_type'    => 'text',
                'setting_value' => "Rent 'n King: You can still complete your rental order. Pay now and choose a new rental start date at checkout: {{payment_link}}",
                'sort_order'    => $sortOrder++,
            ],
            [
                'setting_name'  => 'pod_payment_reminder_3_enabled',
                'setting_title' => 'Enable POD Payment Reminder #3',
                'value_type'    => 'checkbox',
                'setting_value' => '1',
                'sort_order'    => $sortOrder++,
            ],
            [
                'setting_name'  => 'pod_payment_reminder_4_message',
                'setting_title' => 'POD Payment Reminder #4 Message (Closeout)',
                'value_type'    => 'text',
                'setting_value' => "Rent 'n King: It looks like this rental request is no longer needed, so we'll remove the unpaid order from our system. If your plans change, we'd be happy to help. Thank you for considering Rent 'n King.",
                'sort_order'    => $sortOrder++,
            ],
            [
                'setting_name'  => 'pod_payment_reminder_4_enabled',
                'setting_title' => 'Enable POD Payment Reminder #4',
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
                'pod_payment_reminder_1_message',
                'pod_payment_reminder_1_enabled',
                'pod_payment_reminder_2_message',
                'pod_payment_reminder_2_enabled',
                'pod_payment_reminder_3_message',
                'pod_payment_reminder_3_enabled',
                'pod_payment_reminder_4_message',
                'pod_payment_reminder_4_enabled',
            ])->delete();
    }
};
