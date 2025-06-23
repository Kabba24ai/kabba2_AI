<?php

namespace Database\Seeders\Configurations;

use Illuminate\Database\Seeder;

// Models
use App\Models\Configurations\Setting;
use Illuminate\Support\Facades\App;

class SettingSeeder extends Seeder
{
    protected $settings = [];

    public function __construct()
    {
        $this->settings = [
            // Email Settings

            'Email Settings' => [
                // Dev/staging Customer Email
                [
                    'value_type' => 'text',
                    'setting_name' => 'dev_staging_email',
                    'setting_title' => 'Development/Staging Email',
                ],
                [
                    'value_type' => 'text',
                    'setting_name' => 'customer_email_send_email_address',
                    'setting_title' => 'Customer Development/Staging Email',
                ],
            ],

            // Product Setting
            'Product Settings' => [
				[
                    'value_type' => 'number',
                    'setting_name' => 'sales_tax',
                    'setting_title' => 'Sales Tax',
                    'default_value' => 0.0975,
                ],
                [
                    'value_type' => 'number',
                    'setting_name' => 'standard_delivery_range',
                    'setting_title' => 'Standard Delivery Range Up To',
                    'default_value' => 15,
                ],
                [
                    'value_type' => 'number',
                    'setting_name' => 'extended_delivery_range',
                    'setting_title' => 'Extended Delivery Range Up To',
                    'default_value' => 30,
                ],
                [
                    'value_type' => 'boolean',
                    'setting_name' => 'include_extended_range',
                    'setting_title' => 'Include Extended Range Option',
                    'default_value' => true,
                ],
                [
                    'value_type' => 'options',
                    'setting_name' => 'distance_unit',
                    'setting_title' => 'Distance Unit',
                    'setting_options' => json_encode(['Miles', 'Kilometers']),
                    'default_value' => 'Miles',
                ],
                // --- New settings below ---
                [
                    'value_type' => 'textarea',
                    'setting_name' => 'prepaid_fuel_info',
                    'setting_title' => 'Prepaid Fuel Information',
                    'default_value' => '',
                ],
                [
                    'value_type' => 'text',
                    'setting_name' => 'prepaid_fuel_decline_label',
                    'setting_title' => 'Prepaid Fuel Decline Label',
                    'default_value' => 'No Thanks, I\'ll Take The Risk',
                ],
                [
                    'value_type' => 'text',
                    'setting_name' => 'prepaid_fuel_approve_label',
                    'setting_title' => 'Prepaid Fuel Approve Label',
                    'default_value' => 'Keep Safety Harness',
                ],
            ],

            // Contact Us Setting

            'Contact Us Settings' => [
                [
                    'value_type' => 'text',
                    'setting_name' => 'mobile',
                    'setting_title' => 'Mobile',
                ],
                [
                    'value_type' => 'email',
                    'setting_name' => 'email',
                    'setting_title' => 'Email',
                ],
                [
                    'value_type' => 'text',
                    'setting_name' => 'address1',
                    'setting_title' => 'Address 1',
                ],
                [
                    'value_type' => 'text',
                    'setting_name' => 'address2',
                    'setting_title' => 'Address 2',
                ],
                // Enquiry
                [
                    'value_type' => 'text',
                    'setting_name' => 'enquiry-email',
                    'setting_title' => 'Enquiry Email',
                ],
                // Complaint
                [
                    'value_type' => 'text',
                    'setting_name' => 'complaint-email',
                    'setting_title' => 'Complaint Email',
                ],
                // Feedback
                [
                    'value_type' => 'text',
                    'setting_name' => 'feedback-email',
                    'setting_title' => 'Feedback Email',
                ],
            ],
            'Social Media Settings' => [
                [
                    'value_type' => 'text',
                    'setting_name' => 'facebook_page_link',
                    'setting_title' => 'Facebook Page Link',
                ],
                [
                    'value_type' => 'text',
                    'setting_name' => 'linkedin_page_link',
                    'setting_title' => 'LinkedIn Page Link',
                ],
                [
                    'value_type' => 'text',
                    'setting_name' => 'twitter_page_link',
                    'setting_title' => 'Twitter Page Link',
                ],
            ],
        ];
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Read the --update-existing flag (defaults to false)
        $updateExisting = true;

        foreach ($this->settings as $setting_type => $settings) {
            if (empty($settings) || !is_array($settings)) {
                continue;
            }

            foreach ($settings as $setting) {
                $setting_item = Setting::firstOrCreate(
                    [
                        'setting_type' => $setting_type,
                        'setting_name' => $setting['setting_name'],
                    ],
                    [
                        'setting_title' => $setting['setting_title'],
                        'value_type' => $setting['value_type'],
                        'setting_options' => $setting['setting_options'] ?? null,
                        'setting_value' => $setting['default_value'] ?? null,
                    ],
                );

                // Only update if flag is set and record already existed
                if ($updateExisting && !$setting_item->wasRecentlyCreated) {
                    $updateData = [
                        'setting_title' => $setting['setting_title'],
                        'value_type' => $setting['value_type'],
                        'setting_value' => $setting['default_value'] ?? null,
                        'setting_options' => $setting['setting_options'] ?? null,
                    ];
                    // Don't override setting_value unless you want to
                    $setting_item->update($updateData);
                }
            }
        }
    }
}
