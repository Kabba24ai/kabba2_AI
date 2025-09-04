<?php

namespace Database\Seeders\Configurations;

use Illuminate\Database\Seeder;

// Models
use App\Models\Configurations\Setting;
use Illuminate\Support\Facades\App;

class SettingSeeder extends Seeder
{
    private const DECLINE_LABEL = "No Thanks, I'll Take The Risk";
    private const APPROVE_LABEL = 'Keep Safety Harness';

    protected $settings = [];

    public function __construct()
    {
        $this->settings = [];
        $this->addEmailSettings();
        $this->addAllocatedHoursSettings();
        $this->addProductSettings();
        $this->addContactUsSettings();
        $this->addSocialMediaSettings();
        $this->addAdminSettings();
        $this->addPaymentSettings();
    }

    private function addEmailSettings()
    {
        $sortOrder = 1;

        $this->settings['Email Settings'] = [
            [
                'value_type' => 'text',
                'setting_name' => 'dev_staging_email',
                'setting_title' => 'Development/Staging Email',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'customer_email_send_email_address',
                'setting_title' => 'Customer Development/Staging Email',
                'sort_order' => $sortOrder++,
            ],
        ];
    }

    private function addAllocatedHoursSettings()
    {
        $sortOrder = 1;

        $this->settings['Allocated Hours Settings'] = [
            [
                'value_type' => 'number',
                'setting_name' => 'daily_hours',
                'setting_title' => 'Daily Hours',
                'default_value' => 8,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'weekend_hours',
                'setting_title' => 'Weekend Hours',
                'default_value' => 14,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'weekly_hours',
                'setting_title' => 'Weekly Hours',
                'default_value' => 40,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'monthly_hours',
                'setting_title' => 'Monthly Hours',
                'default_value' => 160,
                'sort_order' => $sortOrder++,
            ],
        ];
    }

    private function addProductSettings()
    {
        $sortOrder = 1;

        $this->settings['Product Settings'] = [
            [
                'value_type' => 'number',
                'setting_name' => 'sales_tax',
                'setting_title' => 'Sales Tax',
                'default_value' => 0.0975,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'standard_delivery_range',
                'setting_title' => 'Standard Delivery Range Up To',
                'default_value' => 15,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'extended_delivery_range',
                'setting_title' => 'Extended Delivery Range Up To',
                'default_value' => 30,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'boolean',
                'setting_name' => 'include_extended_range',
                'setting_title' => 'Include Extended Range Option',
                'default_value' => true,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'options',
                'setting_name' => 'distance_unit',
                'setting_title' => 'Distance Unit',
                'setting_options' => json_encode(['Miles', 'Kilometers']),
                'default_value' => 'Miles',
                'sort_order' => $sortOrder++,
            ],
            // --- Prepaid Fuel ---
            [
                'value_type' => 'textarea',
                'setting_name' => 'prepaid_fuel_info',
                'setting_title' => 'Prepaid Fuel Information',
                'default_value' => "I understand that the machine is delivered full of fuel and I’m responsible for returning it full of fuel. If returned without a full tank, I will be charged $8/gallon. If I take the 'Pre-Paid Fuel' option, I can just walk away from this obligation.",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'prepaid_fuel_decline_label',
                'setting_title' => 'Prepaid Fuel Decline Label',
                'default_value' => self::DECLINE_LABEL,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'prepaid_fuel_approve_label',
                'setting_title' => 'Prepaid Fuel Approve Label',
                'default_value' => self::APPROVE_LABEL,
                'sort_order' => $sortOrder++,
            ],
            // --- Prepaid Cleaning ---
            [
                'value_type' => 'textarea',
                'setting_name' => 'prepaid_cleaning_info',
                'setting_title' => 'Prepaid Cleaning Information',
                'default_value' => "I understand I’ll be responsible for bringing the equipment back clean or be charged. This does not cover 'Extreme' cleaning, only standard.",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'prepaid_cleaning_decline_label',
                'setting_title' => 'Prepaid Cleaning Decline Label',
                'default_value' => self::DECLINE_LABEL,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'prepaid_cleaning_approve_label',
                'setting_title' => 'Prepaid Cleaning Approve Label',
                'default_value' => self::APPROVE_LABEL,
                'sort_order' => $sortOrder++,
            ],

            // --- Damage Waiver ---
            [
                'value_type' => 'textarea',
                'setting_name' => 'damage_waiver_info',
                'setting_title' => 'Damage Waiver Information',
                'default_value' => 'By removing the Damage Waiver, you agree to take full financial responsibility for any damage or repairs, or to provide business insurance.',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'damage_waiver_decline_label',
                'setting_title' => 'Damage Waiver Decline Label',
                'default_value' => self::DECLINE_LABEL,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'damage_waiver_approve_label',
                'setting_title' => 'Damage Waiver Approve Label',
                'default_value' => self::APPROVE_LABEL,
                'sort_order' => $sortOrder++,
            ],

            // --- Track Insurance ---
            [
                'value_type' => 'textarea',
                'setting_name' => 'track_insurance_info',
                'setting_title' => 'Track Insurance Information',
                'default_value' => 'By removing the Track Insurance, you agree to take full financial responsibility for any damage or repairs, or to provide business insurance.',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'track_insurance_decline_label',
                'setting_title' => 'Track Insurance Decline Label',
                'default_value' => self::DECLINE_LABEL,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'track_insurance_approve_label',
                'setting_title' => 'Track Insurance Approve Label',
                'default_value' => self::APPROVE_LABEL,
                'sort_order' => $sortOrder++,
            ],
        ];
    }

    private function addContactUsSettings()
    {
        $sortOrder = 1;

        $this->settings['Contact Us Settings'] = [
            [
                'value_type' => 'text',
                'setting_name' => 'mobile',
                'setting_title' => 'Mobile',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'email',
                'setting_name' => 'email',
                'setting_title' => 'Email',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'address1',
                'setting_title' => 'Address 1',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'address2',
                'setting_title' => 'Address 2',
                'sort_order' => $sortOrder++,
            ],
            // Enquiry
            [
                'value_type' => 'text',
                'setting_name' => 'enquiry-email',
                'setting_title' => 'Enquiry Email',
                'sort_order' => $sortOrder++,
            ],
            // Complaint
            [
                'value_type' => 'text',
                'setting_name' => 'complaint-email',
                'setting_title' => 'Complaint Email',
                'sort_order' => $sortOrder++,
            ],
            // Feedback
            [
                'value_type' => 'text',
                'setting_name' => 'feedback-email',
                'setting_title' => 'Feedback Email',
                'sort_order' => $sortOrder++,
            ],
        ];
    }

    private function addSocialMediaSettings()
    {
        $sortOrder = 1;
        $this->settings['Social Media Settings'] = [
            [
                'value_type' => 'text',
                'setting_name' => 'facebook_page_link',
                'setting_title' => 'Facebook Page Link',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'linkedin_page_link',
                'setting_title' => 'LinkedIn Page Link',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'twitter_page_link',
                'setting_title' => 'Twitter Page Link',
                'sort_order' => $sortOrder++,
            ],
        ];
    }

    private function addAdminSettings()
    {
        $this->settings['Admin Settings'] = [
            [
                'value_type' => 'text',
                'setting_name' => 'master_passcode',
                'setting_title' => 'Master Passcode',
                'default_value' => 12345678,
                'sort_order' => 0,
            ],
        ];
    }

    private function addPaymentSettings()
    {
        $sortOrder = 1;
        $this->settings['Payment Settings'] = [
            [
                'value_type' => 'text',
                'setting_name' => 'payment_gateway',
                'setting_title' => 'Payment Gateway',
                'default_value' => 'Authorize.Net',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'payment_api_public_key',
                'setting_title' => 'Payment Api Public Key',
                'default_value' => '2BPBatfc47rsUN2za54WjY48Bc3395AemHWDSL86zbbUyuvhHcQ9LVXw873HYV5D',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'payment_api_key',
                'setting_title' => 'Payment API Key',
                'default_value' => '2Y7eAt88',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'payment_api_secret',
                'setting_title' => 'Payment API Secret',
                'default_value' => '6X2E6Xk46bK7c9xP',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'boolean',
                'setting_name' => 'payment_test_mode',
                'setting_title' => 'Payment Test Mode',
                'default_value' => true,
                'sort_order' => $sortOrder++,
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
        // Use the UPDATE_EXISTING_SETTINGS flag from .env, default to false if not set
        $updateExisting = config('app.seeders.existing_settings_update');

        foreach ($this->settings as $setting_type => $settings) {
            if (empty($settings) || !is_array($settings)) {
                continue;
            }

            // Remove DB settings not listed in array for this type
            $allowedNames = collect($settings)->pluck('setting_name')->all();
            Setting::where('setting_type', $setting_type)->whereNotIn('setting_name', $allowedNames)->delete();

            // Your original create/update code...
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
                        'sort_order' => $setting['sort_order'] ?? 0,
                    ],
                );

                if ($updateExisting && !$setting_item->wasRecentlyCreated) {
                    $updateData = [
                        'setting_title' => $setting['setting_title'],
                        'value_type' => $setting['value_type'],
                        'setting_value' => $setting['default_value'] ?? null,
                        'setting_options' => $setting['setting_options'] ?? null,
                        'sort_order' => $setting['sort_order'] ?? 0,
                    ];
                    $setting_item->update($updateData);
                }
            }
        }
    }
}
