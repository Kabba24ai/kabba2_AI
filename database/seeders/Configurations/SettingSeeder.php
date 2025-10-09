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
        $this->addCommunicationSettings();
        $this->addDefaultSalesFunnelSettings();
        $this->addMailSendSettings();
        $this->addInvoiceSettings();
    }

    private function addDefaultSalesFunnelSettings()
    {
        $sortOrder = 1;

        $this->settings['Default Sales Funnel Settings'] = [
            [
                'value_type' => 'text',
                'setting_name' => 'rental_day_before_truck_message',
                'setting_title' => 'Rental Day Before Truck Message',
                'default_value' => "Reminder: Your rental is scheduled for driver pick-up tomorrow by 9:00 AM. Please have it clean, accessible, with key included and ready for return. Need more time? Call or text (615) 815-6734 to extend your rental. Rentals can’t auto-renew—contact us early to lock in availability and avoid return delays.",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'rental_day_before_store_message',
                'setting_title' => 'Rental Day Before Store Message',
                'default_value' => "Reminder: Your rental is scheduled for driver pick-up tomorrow by 9:00 AM. Please have it clean, accessible, with key included and ready for return.

Need more time? Call or text (615) 815-6734 to extend your rental. Rentals can’t auto-renew—contact us early to lock in availability and avoid return delays.",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'rental_same_day_truck_message',
                'setting_title' => 'Rental Same Day Truck Message',
                'default_value' => "Reminder: Your rental is scheduled for driver pick-up tomorrow by 9:00 AM. Please have it clean, accessible, with key included and ready for return.

Need more time? Call or text (615) 815-6734 to extend your rental. Rentals can’t auto-renew—contact us early to lock in availability and avoid return delays.",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'rental_same_day_store_message',
                'setting_title' => 'Rental Same Day Store Message',
                'default_value' => "Reminder: Your rental is scheduled for driver pick-up tomorrow by 9:00 AM. Please have it clean, accessible, with key included and ready for return.

Need more time? Call or text (615) 815-6734 to extend your rental. Rentals can’t auto-renew—contact us early to lock in availability and avoid return delays.",
                'sort_order' => $sortOrder++,
            ],
        ];
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
        $sortOrder = 0;

        $this->settings['Allocated Hours Settings'] = [
            [
                'value_type' => 'number',
                'setting_name' => 'daily_hours',
                'setting_title' => 'Daily Hours',
                'placeholder' => 'Enter Daily Hours',
                'default_value' => 8,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'weekend_hours',
                'setting_title' => 'Weekend Hours',
                'placeholder' => 'Enter Weekend Hours',
                'default_value' => 14,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'weekly_hours',
                'setting_title' => 'Weekly Hours',
                'placeholder' => 'Enter Weekly Hours',
                'default_value' => 40,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'monthly_hours',
                'setting_title' => 'Monthly Hours',
                'placeholder' => 'Enter Monthly Hours',
                'default_value' => 160,
                'sort_order' => $sortOrder++,
            ],

        ];
    }

    private function addProductSettings()
    {
        $sortOrder = 0;

        $this->settings['Product Settings'] = [
            [
                'value_type' => 'number',
                'setting_name' => 'sales_tax',
                'setting_title' => 'Sales Tax',
                'placeholder' => '8.25',
                'default_value' => 0.0975,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'standard_delivery_range',
                'setting_title' => 'Standard Delivery Range Up To',
                'default_value' => 15,
                'placeholder' => 'Enter numerical distance',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'extended_delivery_range',
                'setting_title' => 'Extended Delivery Range Up To',
                'default_value' => 30,
                'placeholder' => 'Enter numerical distance',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'checkbox',
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

            [
                'value_type' => 'json',
                'setting_name' => 'prepaid_cleaning_rates',
                'setting_title' => 'Prepaid Cleaning Rates',
                'default_value' => json_encode(
                    [
                        [
                            'description' => 'Standard Cleaning',
                            'rate' => 25.0,
                        ],
                        [
                            'description' => 'Heavy Cleaning',
                            'rate' => 50.0,
                        ],
                    ],
                    JSON_UNESCAPED_UNICODE,
                ),
                'sort_order' => $sortOrder++,
            ],

            [
                'value_type' => 'json',
                'setting_name' => 'prepaid_fuel_rates',
                'setting_title' => 'Prepaid Fuel Rates',
                'default_value' => json_encode(
                    [
                        [
                            'description' => 'Full Tank Prepaid',
                            'rate' => 59.99,
                        ],
                        [
                            'description' => 'Half Tank Prepaid',
                            'rate' => 34.5,
                        ],
                    ],
                    JSON_UNESCAPED_UNICODE,
                ),
                // 'default_value' => json_encode([]),
                'sort_order' => $sortOrder++,
            ],

            [
                'value_type' => 'number',
                'setting_name' => 'damage_waiver_percentage',
                'setting_title' => 'Damage Waiver Percentage',
                'default_value' => 10,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'overage_rate_percentage',
                'setting_title' => 'Overage Rate Percentage',
                'default_value' => 16.7,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'small_standard_delivery_fee',
                'setting_title' => 'Small Standard Delivery Fee',
                'default_value' => 10,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'small_extended_delivery_fee',
                'setting_title' => 'Small Extended Delivery Fee',
                'default_value' => 10,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_standard_delivery_fee',
                'setting_title' => 'Medium Standard Delivery Fee',
                'default_value' => 20,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_extended_delivery_fee',
                'setting_title' => 'Medium Extended Delivery Fee',
                'default_value' => 20,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_standard_delivery_fee',
                'setting_title' => 'Large Standard Delivery Fee',
                'default_value' => 30,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_extended_delivery_fee',
                'setting_title' => 'Large Extended Delivery Fee',
                'default_value' => 30,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_standard_delivery_fee',
                'setting_title' => 'X-Large Standard Delivery Fee',
                'default_value' => 40,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_extended_delivery_fee',
                'setting_title' => 'X-Large Extended Delivery Fee',
                'default_value' => 40,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_standard_delivery_fee',
                'setting_title' => '2X-Large Standard Delivery Fee',
                'default_value' => 50,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_extended_delivery_fee',
                'setting_title' => '2X-Large Extended Delivery Fee',
                'default_value' => 50,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_standard_delivery_fee',
                'setting_title' => 'Commercial Standard Delivery Fee',
                'default_value' => 60,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_extended_delivery_fee',
                'setting_title' => 'Commercial Extended Delivery Fee',
                'default_value' => 60,
                'sort_order' => $sortOrder++,
            ],

            //--- Track Insurance ---
            [
                'value_type' => 'number',
                'setting_name' => 'small_daily_track_insurance_fee',
                'setting_title' => 'Small Daily Track Insurance Fee',
                'default_value' => 10,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_daily_track_insurance_fee',
                'setting_title' => 'Medium Daily Track Insurance Fee',
                'default_value' => 20,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_daily_track_insurance_fee',
                'setting_title' => 'Large Daily Track Insurance Fee',
                'default_value' => 30,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_daily_track_insurance_fee',
                'setting_title' => 'X-Large Daily Track Insurance Fee',
                'default_value' => 40,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_daily_track_insurance_fee',
                'setting_title' => '2X-Large Daily Track Insurance Fee',
                'default_value' => 50,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_daily_track_insurance_fee',
                'setting_title' => 'Commercial Daily Track Insurance Fee',
                'default_value' => 60,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'small_weekend_track_insurance_fee',
                'setting_title' => 'Small Weekend Track Insurance Fee',
                'default_value' => 70,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_weekend_track_insurance_fee',
                'setting_title' => 'Medium Weekend Track Insurance Fee',
                'default_value' => 80,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_weekend_track_insurance_fee',
                'setting_title' => 'Large Weekend Track Insurance Fee',
                'default_value' => 90,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_weekend_track_insurance_fee',
                'setting_title' => 'X-Large Weekend Track Insurance Fee',
                'default_value' => 100,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_weekend_track_insurance_fee',
                'setting_title' => '2X-Large Weekend Track Insurance Fee',
                'default_value' => 110,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_weekend_track_insurance_fee',
                'setting_title' => 'Commercial Weekend Track Insurance Fee',
                'default_value' => 120,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'small_weekly_track_insurance_fee',
                'setting_title' => 'Small Weekly Track Insurance Fee',
                'default_value' => 130,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_weekly_track_insurance_fee',
                'setting_title' => 'Medium Weekly Track Insurance Fee',
                'default_value' => 140,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_weekly_track_insurance_fee',
                'setting_title' => 'Large Weekly Track Insurance Fee',
                'default_value' => 150,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_weekly_track_insurance_fee',
                'setting_title' => 'X-Large Weekly Track Insurance Fee',
                'default_value' => 160,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_weekly_track_insurance_fee',
                'setting_title' => '2X-Large Weekly Track Insurance Fee',
                'default_value' => 170,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_weekly_track_insurance_fee',
                'setting_title' => 'Commercial Weekly Track Insurance Fee',
                'default_value' => 180,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'small_monthly_track_insurance_fee',
                'setting_title' => 'Small Monthly Track Insurance Fee',
                'default_value' => 190,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_monthly_track_insurance_fee',
                'setting_title' => 'Medium Monthly Track Insurance Fee',
                'default_value' => 200,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_monthly_track_insurance_fee',
                'setting_title' => 'Large Monthly Track Insurance Fee',
                'default_value' => 210,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_monthly_track_insurance_fee',
                'setting_title' => 'X-Large Monthly Track Insurance Fee',
                'default_value' => 220,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_monthly_track_insurance_fee',
                'setting_title' => '2X-Large Monthly Track Insurance Fee',
                'default_value' => 230,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_monthly_track_insurance_fee',
                'setting_title' => 'Commercial Monthly Track Insurance Fee',
                'default_value' => 240,
                'sort_order' => $sortOrder++,
            ],



            // --- Prepaid Fuel ---
            [
                'value_type' => 'textarea',
                'setting_name' => 'prepaid_fuel_info',
                'setting_title' => 'Prepaid Fuel Message',
                'placeholder' => 'Enter message for prepaid fuel popup...',
                'default_value' => "I understand that the machine is delivered full of fuel and I’m responsible for returning it full of fuel. If returned without a full tank, I will be charged $8/gallon. If I take the 'Pre-Paid Fuel' option, I can just walk away from this obligation.",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'prepaid_fuel_decline_label',
                'setting_title' => 'Decline Button Label',
                'placeholder' => 'Enter decline button text',
                'default_value' => self::DECLINE_LABEL,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'prepaid_fuel_approve_label',
                'setting_title' => 'Accept Button Label',
                'placeholder' => 'Enter accept button text',
                'default_value' => self::APPROVE_LABEL,
                'sort_order' => $sortOrder++,
            ],
            // --- Prepaid Cleaning ---

            [
                'value_type' => 'textarea',
                'setting_name' => 'prepaid_cleaning_info',
                'setting_title' => 'Prepaid Cleaning Message',
                'placeholder' => 'Enter message for prepaid cleaning popup...',
                'default_value' => "I understand I’ll be responsible for bringing the equipment back clean or be charged. This does not cover 'Extreme' cleaning, only standard.",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'prepaid_cleaning_decline_label',
                'setting_title' => 'Decline Button Label',
                'placeholder' => 'Enter decline button text',
                'default_value' => self::DECLINE_LABEL,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'prepaid_cleaning_approve_label',
                'setting_title' => 'Accept Button Label',
                'placeholder' => 'Enter accept button text',
                'default_value' => self::APPROVE_LABEL,
                'sort_order' => $sortOrder++,
            ],

            // --- Track Insurance ---
            [
                'value_type' => 'textarea',
                'setting_name' => 'track_insurance_info',
                'setting_title' => 'Thrown Track Insurance Message',
                'placeholder' => 'Enter message for thrown track insurance popup...',
                'default_value' => 'By removing the Track Insurance, you agree to take full financial responsibility for any damage or repairs, or to provide business insurance.',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'track_insurance_decline_label',
                'setting_title' => 'Decline Button Label',
                'placeholder' => 'Enter decline button text',
                'default_value' => self::DECLINE_LABEL,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'track_insurance_approve_label',
                'setting_title' => 'Accept Button Label',
                'placeholder' => 'Enter accept button text',
                'default_value' => self::APPROVE_LABEL,
                'sort_order' => $sortOrder++,
            ],

            // --- Damage Waiver ---
            [
                'value_type' => 'textarea',
                'setting_name' => 'damage_waiver_info',
                'setting_title' => 'Damage Waiver Protection Message',
                'placeholder' => 'Enter message for damage waiver protection popup...',
                'default_value' => 'By removing the Damage Waiver, you agree to take full financial responsibility for any damage or repairs, or to provide business insurance.',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'damage_waiver_decline_label',
                'setting_title' => 'Decline Button Label',
                'placeholder' => 'Enter decline button text',
                'default_value' => self::DECLINE_LABEL,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'damage_waiver_approve_label',
                'setting_title' => 'Accept Button Label',
                'placeholder' => 'Enter accept button text',
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
                'setting_name' => 'address1',
                'setting_title' => 'Address 1',
                'default_value' => '10296 Highway 46, Bon Aqua, TN 37025',
                'placeholder' => 'Enter address',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'address2',
                'setting_title' => 'Address 2',
                'default_value' => '4385 SR-48, Charlotte, TN 37055',
                'placeholder' => 'Enter address',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'sales_phone',
                'setting_title' => 'Phone - Sales',
                'placeholder' => 'USA (xxx) xxx-xxxx',
                'default_value' => '(615) 815-6734',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'support_phone',
                'setting_title' => 'Phone - Support',
                'placeholder' => 'USA (xxx) xxx-xxxx',
                'default_value' => '(615) 815-6734',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'email',
                'setting_name' => 'sales_email',
                'setting_title' => 'Email - Sales',
                'placeholder' => 'Enter sales email',
                'default_value' => 'sales@rentnking.com',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'email',
                'setting_name' => 'support_email',
                'setting_title' => 'Email - Support',
                'placeholder' => 'Enter support email',
                'default_value' => 'sales@rentnking.com',
                'sort_order' => $sortOrder++,
            ],

        ];
    }

    private function addSocialMediaSettings()
    {
        $sortOrder = 1;
        $this->settings['Social Media Settings'] = [
            [
                'value_type' => 'setting_module_title',
                'setting_name' => 'social_media_profiles_title',
                'setting_title' => 'Social Media Profiles',
                'sort_order' => $sortOrder++,
            ],

            [
                'value_type' => 'text',
                'setting_name' => 'facebook_page_link',
                'setting_title' => 'Facebook Page Link',
                'placeholder' => 'https://www.facebook.com/yourpage',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'twitter_page_link',
                'setting_title' => 'Twitter Page Link',
                'placeholder' => 'https://www.twitter.com/youraccount',
                'sort_order' => $sortOrder++,
            ],

            [
                'value_type' => 'text',
                'setting_name' => 'instagram_page_link',
                'setting_title' => 'Instagram URL',
                'placeholder' => 'https://www.instagram.com/youracount',
                'sort_order' => $sortOrder++,
            ],

            [
                'value_type' => 'text',
                'setting_name' => 'linkedin_page_link',
                'setting_title' => 'LinkedIn Page Link',
                'placeholder' => 'https://www.linkedIn.com/company/yourcompany',
                'sort_order' => $sortOrder++,
            ],

            [
                'value_type' => 'text',
                'setting_name' => 'youtube_page_link',
                'setting_title' => 'Youtube Page Link',
                'placeholder' => 'https://www.youtube.com/c/yourchannel',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'tiktok_page_link',
                'setting_title' => 'Tiktok Page Link',
                'placeholder' => 'https://www.tiktok.com/@youraccount',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'pinterest_page_link',
                'setting_title' => 'Pinterest Page Link',
                'placeholder' => 'https://www.pinterest.com/youraccount',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'snapchat_page_link',
                'setting_title' => 'Snapchat Page Link',
                'placeholder' => 'https://www.snapchat.com/add/youraccount',
                'sort_order' => $sortOrder++,
            ],

            [
                'value_type' => 'setting_module_title',
                'setting_name' => 'display_settings_title',
                'setting_title' => 'Display Settings',
                'sort_order' => $sortOrder++,
            ],

            [
                'value_type' => 'checkbox',
                'setting_name' => 'show_social_media_icons',
                'setting_title' => 'Show Social Media Icons',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'checkbox',
                'setting_name' => 'enable_social_sharing',
                'setting_title' => 'Enable Social Sharing',
                'sort_order' => $sortOrder++,
            ],
        ];
    }

    private function addInvoiceSettings()
    {
        $sortOrder = 1;
        $this->settings['Invoice Settings'] = [
            [
                'value_type' => 'number',
                'setting_name' => 'due_date_pay_upon_receipt',
                'setting_title' => 'Due Date Pay Upon Receipt',
                'placeholder' => '5',
                'default_value' => 5,
                'sort_order' => $sortOrder++,
            ],
        ];
    }

    private function addAdminSettings()
    {
        $sortOrder = 1;
        $this->settings['Admin Settings'] = [
            [
                'value_type' => 'password',
                'setting_name' => 'master_passcode',
                'setting_title' => 'Master Passcode',
                'default_value' => 12345678,
                'placeholder' => 'Enter master passcode',
                'is_secure_field' => 1,
                'is_encrypted' => 1,
                'is_required' => 1,
                'is_eye_toggle' => 1,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'password',
                'setting_name' => 'master_password_entry',
                'setting_title' => 'Master Password - Entry',
                'default_value' => 12345678,
                'placeholder' => 'Enter Password for access/edit',
                'is_secure_field' => 1,
                'is_required' => 1,
                'is_eye_toggle' => 1,
                'sort_order' => $sortOrder++,
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
                'placeholder' => 'Enter payment gateway name',
                'is_secure_field' => 1,
                'default_value' => 'Authorize.Net',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'payment_api_public_key',
                'setting_title' => 'Payment API - Public Key',
                'placeholder' => 'Enter public API key',
                'is_secure_field' => 1,
                'default_value' => '2BPBatfc47rsUN2za54WjY48Bc3395AemHWDSL86zbbUyuvhHcQ9LVXw873HYV5D',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'password',
                'setting_name' => 'payment_api_key',
                'setting_title' => 'Payment API Key',
                'placeholder' => 'Enter API key',
                'is_secure_field' => 1,
                'is_encrypted' => 1,
                'is_eye_toggle' => 1,
                'default_value' => '2Y7eAt88',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'payment_api_secret',
                'setting_title' => 'Payment API Secret Key',
                'placeholder' => 'Enter secret key',
                'is_secure_field' => 1,
                'is_required' => 1,
                'is_eye_toggle' => 1,
                'default_value' => '6X2E6Xk46bK7c9xP',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'boolean',
                'setting_name' => 'payment_test_mode',
                'setting_title' => 'Payment Test Mode',
                'is_secure_field' => 1,
                'default_value' => true,
                'sort_order' => $sortOrder++,
            ],
        ];
    }

    private function addCommunicationSettings()
    {
        $sortOrder = 1;

        $this->settings['Communication Settings'] = [
            [
                'value_type' => 'text',
                'setting_name' => 'sms_gateway',
                'setting_title' => 'SMS Gateway',
                'placeholder' => 'Enter SMS gateway name',
                'is_secure_field' => 1,
                'default_value' => 'Twilio',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'twilio_sid',
                'setting_title' => 'Twilio SID',
                'placeholder' => 'Enter Twilio SID',
                'is_secure_field' => 1,
                'is_encrypted' => 1,
                'is_eye_toggle' => 1,
                'default_value' => 'AC9bf505b331e6e08f428ea0f143194c9f',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'twilio_auth_token',
                'setting_title' => 'Twilio Auth Token',
                'placeholder' => 'Enter Twilio Auth Token',
                'is_secure_field' => 1,
                'is_required' => 1,
                'is_eye_toggle' => 1,
                'default_value' => '423d79bcf93860d3dd112d76a6d0495d',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'twilio_from_number',
                'setting_title' => 'Twilio From Number',
                'placeholder' => 'Enter Twilio from number',
                'is_secure_field' => 1,
                'default_value' => '+16157032809',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'twilio_messaging_service_sid',
                'setting_title' => 'Twilio Messaging Service SID',
                'placeholder' => 'Enter Twilio Messaging Service SID',
                'is_secure_field' => 1,
                'default_value' => 'MGaadb9b9a99198fb912890dd06a1fb2fc',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'boolean',
                'setting_name' => 'sms_test_mode',
                'setting_title' => 'SMS Test Mode',
                'is_secure_field' => 1,
                'default_value' => true,
                'sort_order' => $sortOrder++,
            ],
        ];
    }


    private function addMailSendSettings()
    {
        $sortOrder = 1;

        $this->settings['Mail Send Settings'] = [
            [
                'value_type'   => 'text',
                'setting_name' => 'mail_mailer',
                'setting_title' => 'Mail Mailer',
                'default_value' => 'smtp',
                'sort_order'   => $sortOrder++,
            ],
            [
                'value_type'   => 'text',
                'setting_name' => 'mail_host',
                'setting_title' => 'Mail Host',
                'default_value' => 'mail.kabba.ai',
                'sort_order'   => $sortOrder++,
            ],
            [
                'value_type'   => 'number',
                'setting_name' => 'mail_port',
                'setting_title' => 'Mail Port',
                'default_value' => 465,
                'sort_order'   => $sortOrder++,
            ],
            [
                'value_type'   => 'text',
                'setting_name' => 'mail_username',
                'setting_title' => 'Mail Username',
                'is_secure_field' => 1,
                'is_encrypted'    => 1,
                'is_required'     => 1,
                'is_eye_toggle'   => 1,
                'default_value' => 'billing@kabba.ai',
                'sort_order'   => $sortOrder++,
            ],
            [
                'value_type'   => 'password',
                'setting_name' => 'mail_password',
                'setting_title' => 'Mail Password',
                'default_value' => '-PP}uX0C%}#8',
                'is_secure_field' => 1,
                'is_encrypted'    => 1,
                'is_required'     => 1,
                'is_eye_toggle'   => 1,
                'sort_order'   => $sortOrder++,
            ],
            [
                'value_type'   => 'text',
                'setting_name' => 'mail_encryption',
                'setting_title' => 'Mail Encryption',
                'default_value' => 'tls',
                'sort_order'   => $sortOrder++,
            ],
            [
                'value_type'   => 'text',
                'setting_name' => 'mail_from_address',
                'setting_title' => 'Mail From Address',
                'default_value' => 'billing@kabba.ai',
                'sort_order'   => $sortOrder++,
            ],
            [
                'value_type'   => 'text',
                'setting_name' => 'mail_from_name',
                'setting_title' => 'Mail From Name',
                'default_value' => config('app.name'),
                'sort_order'   => $sortOrder++,
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
                        'sort_order' => $setting['sort_order'] ?? 0,
                        'placeholder' => $setting['placeholder'] ?? null,
                        'is_secure_field' => $setting['is_secure_field'] ?? 0,
                        'is_required' => $setting['is_required'] ?? 0,
                        'is_encrypted' => $setting['is_encrypted'] ?? 0,
                        'is_eye_toggle' => $setting['is_eye_toggle'] ?? 0,
                    ],
                );

                //  Now trigger mutator
                if (isset($setting['default_value'])) {
                    $setting_item->setting_value = $setting['default_value'];
                    $setting_item->save();
                }

                if ($updateExisting && !$setting_item->wasRecentlyCreated) {
                    $setting_item->fill([
                        'setting_title' => $setting['setting_title'],
                        'value_type' => $setting['value_type'],
                        'setting_options' => $setting['setting_options'] ?? null,
                        'sort_order' => $setting['sort_order'] ?? 0,
                        'placeholder' => $setting['placeholder'] ?? null,
                        'is_secure_field' => $setting['is_secure_field'] ?? 0,
                        'is_required' => $setting['is_required'] ?? 0,
                        'is_encrypted' => $setting['is_encrypted'] ?? 0,
                        'is_eye_toggle' => $setting['is_eye_toggle'] ?? 0,
                    ]);

                    if (isset($setting['default_value'])) {
                        $setting_item->setting_value = $setting['default_value']; //  goes through mutator
                    }

                    $setting_item->save();
                }
            }
        }
    }
}
