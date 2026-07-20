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
        $this->addPriceRateMultiplierSettings();
        $this->addContactUsSettings();
        $this->addCompanySettings();
        $this->addPriceListSettings();
        $this->addSocialMediaSettings();
        $this->addAdminSettings();
        $this->addPaymentSettings();
        $this->addCommunicationSettings();
        $this->addDefaultSalesFunnelSettings();
        $this->addMailSendSettings();
        $this->addInvoiceSettings();
        $this->addPriceSettings();
        $this->addPrivacyPolicySettings();
        $this->addTermsConditionsSettings();

    }

    private function addDefaultSalesFunnelSettings()
    {
        $sortOrder = 1;

        $this->settings['Default Sales Funnel Settings'] = [
            [
                'value_type' => 'text',
                'setting_name' => 'truck_delivery_cod_order_message',
                'setting_title' => 'Truck Delivery POD Order Message',
                'default_value' => "Thanks for placing a POD order! POD reservations are not guaranteed, and inventory may rent out until payment is made. We’re unable to load or send a delivery truck until payment is received. To secure your equipment, please complete payment. Questions? Call or text (615) 815-6734.",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'checkbox',
                'setting_name' => 'truck_delivery_cod_message_enabled',
                'setting_title' => 'Enable Truck Delivery POD Message',
                'default_value' => true,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'store_delivery_cod_order_message',
                'setting_title' => 'Store Delivery POD Order Message',
                'default_value' => "Thank you for your POD order! Please note that POD is a soft reservation only, and equipment isn’t held until payment is made. For in-store pickup, we cannot release the machine until the order is paid. To lock in your rental, please call to complete payment. Call or text (615) 815-6734.",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'checkbox',
                'setting_name' => 'store_delivery_cod_message_enabled',
                'setting_title' => 'Enable Store Delivery POD Message',
                'default_value' => true,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'truck_delivery_same_day_cod_order_message',
                'setting_title' => 'Truck Delivery POD Order Message',
                'default_value' => "Good morning! Your rental is set for delivery today. Because this is a POD order, payment must be made before we can load the unit. Please call us at (615) 815-6734 to make your payment so we can get your equipment on the way.",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'checkbox',
                'setting_name' => 'truck_delivery_same_day_cod_message_enabled',
                'setting_title' => 'Enable Truck Delivery POD Message',
                'default_value' => true,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'store_delivery_same_day_cod_order_message',
                'setting_title' => 'Store Delivery POD Order Message',
                'default_value' => "Before heading in, please call us at (615) 815-6734 so we can check availability for your POD order. POD doesn’t guarantee the equipment, though it’s usually still available. A quick call ensures everything is ready when you arrive.",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'checkbox',
                'setting_name' => 'store_delivery_same_day_cod_message_enabled',
                'setting_title' => 'Enable Store Delivery POD Message',
                'default_value' => true,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'rental_delivery_day_before_truck_message',
                'setting_title' => 'Rental Day Before Truck Message',
                'default_value' => "Your rental delivery is scheduled for tomorrow. Our driver will call or text you with a firm ETA once they are on the way, so you won’t be stuck waiting. Please be available at delivery so we can review the equipment and show you how to use it efficiently. Let us know of any special delivery conditions. Call or text (615) 815-6734.",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'rental_delivery_day_before_store_message',
                'setting_title' => 'Rental Delivery Day Before Store Message',
                'default_value' => "Just a reminder: Your rental starts tomorrow. We open at 7:00 AM and work to get equipment out as early as possible. Daily/Weekly/Monthly rentals officially start at 9:00 AM, and Weekend Specials start at 2:00 PM, but we always try to put the equipment in your hands as early as we can. Call or text us at (615) 815-6734 with questions.",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'rental_delivery_same_day_truck_message',
                'setting_title' => 'Rental Delivery Same Day Truck Message',
                'default_value' => "Reminder: Your rental equipment is being delivered today. Our driver will call or text with a firm ETA once en route. Please notify the driver if there are any special conditions required for a successful delivery i.e. \"unload on the street\" or \"limited turn around space\" or \"gate code required\", etc. For help, call or text (615) 815-6734.",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'rental_delivery_same_day_store_message',
                'setting_title' => 'Rental Delivery Same Day Store Message',
                'default_value' => "Good morning! We’re open at 7:00 AM and aim to have equipment ready early. Daily/Weekly rentals officially start at 9:00 AM, and Weekend Specials at 2:00 PM, though many customers pick up earlier when equipment is prepped. Call or text us at (615) 815-6734 if you need anything.",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'checkbox',
                'setting_name' => 'rental_delivery_day_before_truck_message_enabled',
                'setting_title' => 'Enable Rental Delivery Day Before Truck Message',
                'default_value' => true,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'checkbox',
                'setting_name' => 'rental_delivery_day_before_store_message_enabled',
                'setting_title' => 'Enable Rental Delivery Day Before Store Message',
                'default_value' => true,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'checkbox',
                'setting_name' => 'rental_delivery_same_day_truck_message_enabled',
                'setting_title' => 'Enable Rental Delivery Same Day Truck Message',
                'default_value' => true,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'checkbox',
                'setting_name' => 'rental_delivery_same_day_store_message_enabled',
                'setting_title' => 'Enable Rental Delivery Same Day Store Message',
                'default_value' => true,
                'sort_order' => $sortOrder++,
            ],

            [
                'value_type' => 'text',
                'setting_name' => 'rental_return_day_before_truck_message',
                'setting_title' => 'Rental Return Day Before Truck Message',
                'default_value' => "Reminder: Your rental is scheduled for driver pick-up tomorrow by {{pickup_time}}. Please have it clean, accessible, with the key included, and ready for return. Need more time? Rentals can’t auto-renew, but we’ll do everything we can to extend it for you if the schedule allows—just call or text (615) 815-6734 and we’ll do our best to make it happen.",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'rental_return_day_before_store_message',
                'setting_title' => 'Rental Return Day Before Store Message',
                'default_value' => "Reminder: Your rental is scheduled to be returned to our store tomorrow by {{pickup_time}}. Please plan for drop-off and return it clean, full of fuel, and with the key included. Need more time? Rentals can’t auto-renew, but call or text (615) 815-6734 and we’ll do our best to extend it if availability allows.",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'rental_return_same_day_truck_message',
                'setting_title' => 'Rental Return Same Day Truck Message',
                'default_value' => "Good morning! Our drivers will be out today to pick up your rental. Please have the unit clean, accessible, and ready to go with the key included. If you need to extend your rental, please call or text us immediately before the drivers leave so we can do our best to make it happen. (615) 815-6734",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'rental_return_same_day_store_message',
                'setting_title' => 'Rental Return Same Day Store Message',
                'default_value' => "Good morning! This is a reminder that your rental is due back to our store today by {{pickup_time}}. Please return it clean, full of fuel, and with the key included. Need an extension? Call or text us immediately this morning so we can do our best to make it happen before the day gets booked. (615) 815-6734",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'checkbox',
                'setting_name' => 'rental_return_day_before_truck_message_enabled',
                'setting_title' => 'Enable Rental Return Day Before Truck Message',
                'default_value' => true,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'checkbox',
                'setting_name' => 'rental_return_day_before_store_message_enabled',
                'setting_title' => 'Enable Rental Return Day Before Store Message',
                'default_value' => true,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'checkbox',
                'setting_name' => 'rental_return_same_day_truck_message_enabled',
                'setting_title' => 'Enable Rental Return Same Day Truck Message',
                'default_value' => true,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'checkbox',
                'setting_name' => 'rental_return_same_day_store_message_enabled',
                'setting_title' => 'Enable Rental Return Same Day Store Message',
                'default_value' => true,
                'sort_order' => $sortOrder++,
            ],

            // POD Payment Reminder Messages
            [
                'value_type' => 'text',
                'setting_name' => 'pod_payment_reminder_1_message',
                'setting_title' => 'POD Payment Reminder #1 Message',
                'default_value' => "Rent 'n King: Your rental order is not reserved until payment is complete. Pay here to secure your order: {{payment_link}}",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'checkbox',
                'setting_name' => 'pod_payment_reminder_1_enabled',
                'setting_title' => 'Enable POD Payment Reminder #1',
                'default_value' => true,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'pod_payment_reminder_2_message',
                'setting_title' => 'POD Payment Reminder #2 Message',
                'default_value' => "Rent 'n King reminder: Your rental starts today, but payment is still needed before your order is guaranteed. Pay here: {{payment_link}}",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'checkbox',
                'setting_name' => 'pod_payment_reminder_2_enabled',
                'setting_title' => 'Enable POD Payment Reminder #2',
                'default_value' => true,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'pod_payment_reminder_3_message',
                'setting_title' => 'POD Payment Reminder #3 Message (Last Chance)',
                'default_value' => "Rent 'n King: You can still complete your rental order. Pay now and choose a new rental start date at checkout: {{payment_link}}",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'checkbox',
                'setting_name' => 'pod_payment_reminder_3_enabled',
                'setting_title' => 'Enable POD Payment Reminder #3',
                'default_value' => true,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'pod_payment_reminder_4_message',
                'setting_title' => 'POD Payment Reminder #4 Message (Closeout)',
                'default_value' => "Rent 'n King: It looks like this rental request is no longer needed, so we'll remove the unpaid order from our system. If your plans change, we'd be happy to help. Thank you for considering Rent 'n King.",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'checkbox',
                'setting_name' => 'pod_payment_reminder_4_enabled',
                'setting_title' => 'Enable POD Payment Reminder #4',
                'default_value' => true,
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

    private function addPriceSettings()
    {
        $sortOrder = 1;

        $this->settings['Price Settings'] = [
            [
                'value_type' => 'text',
                'setting_name' => 'diesel_price_per_gallon',
                'setting_title' => 'Diesel / Gallon',
                'sort_order' => $sortOrder++,
                'is_required' => true,
                'default_value' => '3.70',
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'gas_price_per_gallon',
                'setting_title' => 'Gas / Gallon',
                'sort_order' => $sortOrder++,
                'is_required' => true,
                'default_value' => '2.95',
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'def_price_per_gallon',
                'setting_title' => 'DEF / Gallon',
                'sort_order' => $sortOrder++,
                'is_required' => true,
                'default_value' => '3.98',
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

    private function addPriceRateMultiplierSettings()
    {
        $sortOrder = 0;

        $this->settings['Price Rate Multiplier Settings'] = [
            [
                'value_type' => 'number',
                'setting_name' => 'weekend_multiplier',
                'setting_title' => 'Weekend Rate Multiplier',
                'placeholder' => '1.5',
                'default_value' => null,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'weekly_multiplier',
                'setting_title' => 'Weekly Rate Multiplier',
                'placeholder' => '4.0',
                'default_value' => null,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'monthly_multiplier',
                'setting_title' => 'Monthly Rate Multiplier',
                'placeholder' => '16.0',
                'default_value' => null,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'allowed_price_endings',
                'setting_title' => 'Allowed Price Endings',
                'placeholder' => 'e.g. 4,7',
                'default_value' => '4,7',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'hundred_entry_threshold',
                'setting_title' => 'Hundred-Entry Threshold',
                'placeholder' => 'Whole dollars, e.g. 10',
                'default_value' => '10',
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
                'setting_name' => 'special_taxes',
                'setting_title' => 'Special Taxes',
                'placeholder' => '0.00',
                'default_value' => 0,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'special_taxes_description',
                'setting_title' => 'Special Taxes Description',
                'placeholder' => 'Enter description',
                'default_value' => '',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'added_fees',
                'setting_title' => 'Added Fees',
                'placeholder' => '0.00',
                'default_value' => 0,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'added_fees_description',
                'setting_title' => 'Added Fees Description',
                'placeholder' => 'Enter description',
                'default_value' => '',
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
            // Custom 1–4 delivery tiers: administrative labels only; blank
            // distance = tier not configured, so no default values are seeded.
            [
                'value_type' => 'number',
                'setting_name' => 'custom_1_delivery_range',
                'setting_title' => 'Custom 1 Delivery Range Up To',
                'placeholder' => 'Enter numerical distance',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'custom_2_delivery_range',
                'setting_title' => 'Custom 2 Delivery Range Up To',
                'placeholder' => 'Enter numerical distance',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'custom_3_delivery_range',
                'setting_title' => 'Custom 3 Delivery Range Up To',
                'placeholder' => 'Enter numerical distance',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'custom_4_delivery_range',
                'setting_title' => 'Custom 4 Delivery Range Up To',
                'placeholder' => 'Enter numerical distance',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'credit_card_processing_fee',
                'setting_title' => 'Credit Card Processing Fee',
                'default_value' => 0,
                'placeholder' => '3.00',
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
                            'description' => 'Easy: Stump Grinder',
                            'rate' => 19,
                        ],
                        [
                            'description' => 'Moderate: Trencher',
                            'rate' => 29,
                        ],
                        [
                            'description' => 'Large: Skid Steer',
                            'rate' => 39,
                        ],
                        [
                            'description' => 'Large w/Cab:',
                            'rate' => 49,
                        ],
                        [
                            'description' => 'Commercial:',
                            'rate' => 67,
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
                            'description' => 'Gas: xSmall: 1 Gallon',
                            'rate' => 3,
                        ],
                        [
                            'description' => 'Gas: Small: 2 Gallons',
                            'rate' => 6,
                        ],
                        [
                            'description' => 'Gas: Medium: Georgia Buggy',
                            'rate' => 14,
                        ],
                        [
                            'description' => 'Gas: Large: Compactor',
                            'rate' => 18,
                        ],
                        [
                            'description' => 'Diesel: Small: 1.5 Ton Ex',
                            'rate' => 17,
                        ],
                        [
                            'description' => 'Diesel: Medium: Mini Skid',
                            'rate' => 37,
                        ],
                        [
                            'description' => 'Diesel: Large: Skid Steer',
                            'rate' => 67,
                        ],
                        [
                            'description' => 'Diesel: xLarge: TB290',
                            'rate' => 97,
                        ],
                        [
                            'description' => 'Diesel: Commercial: Dozer',
                            'rate' => 147,
                        ]
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
                'default_value' => 14,
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
                'default_value' => 49,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'small_extended_delivery_fee',
                'setting_title' => 'Small Extended Delivery Fee',
                'default_value' => 69,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_standard_delivery_fee',
                'setting_title' => 'Medium Standard Delivery Fee',
                'default_value' => 69,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_extended_delivery_fee',
                'setting_title' => 'Medium Extended Delivery Fee',
                'default_value' => 94,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_standard_delivery_fee',
                'setting_title' => 'Large Standard Delivery Fee',
                'default_value' => 89,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_extended_delivery_fee',
                'setting_title' => 'Large Extended Delivery Fee',
                'default_value' => 124,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_standard_delivery_fee',
                'setting_title' => 'X-Large Standard Delivery Fee',
                'default_value' => 97,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_extended_delivery_fee',
                'setting_title' => 'X-Large Extended Delivery Fee',
                'default_value' => 139,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_standard_delivery_fee',
                'setting_title' => '2X-Large Standard Delivery Fee',
                'default_value' => 124,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_extended_delivery_fee',
                'setting_title' => '2X-Large Extended Delivery Fee',
                'default_value' => 164,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_standard_delivery_fee',
                'setting_title' => 'Commercial Standard Delivery Fee',
                'default_value' => 375,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_extended_delivery_fee',
                'setting_title' => 'Commercial Extended Delivery Fee',
                'default_value' => 450,
                'sort_order' => $sortOrder++,
            ],
            // Custom 1–4 one-way delivery rates per equipment size.
            // No default values: blank/NULL = rate not configured, while an
            // administrator-entered 0 means intentionally free.
            [
                'value_type' => 'number',
                'setting_name' => 'small_custom_1_delivery_fee',
                'setting_title' => 'Small Custom 1 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'small_custom_2_delivery_fee',
                'setting_title' => 'Small Custom 2 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'small_custom_3_delivery_fee',
                'setting_title' => 'Small Custom 3 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'small_custom_4_delivery_fee',
                'setting_title' => 'Small Custom 4 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_custom_1_delivery_fee',
                'setting_title' => 'Medium Custom 1 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_custom_2_delivery_fee',
                'setting_title' => 'Medium Custom 2 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_custom_3_delivery_fee',
                'setting_title' => 'Medium Custom 3 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_custom_4_delivery_fee',
                'setting_title' => 'Medium Custom 4 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_custom_1_delivery_fee',
                'setting_title' => 'Large Custom 1 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_custom_2_delivery_fee',
                'setting_title' => 'Large Custom 2 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_custom_3_delivery_fee',
                'setting_title' => 'Large Custom 3 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_custom_4_delivery_fee',
                'setting_title' => 'Large Custom 4 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_custom_1_delivery_fee',
                'setting_title' => 'X-Large Custom 1 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_custom_2_delivery_fee',
                'setting_title' => 'X-Large Custom 2 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_custom_3_delivery_fee',
                'setting_title' => 'X-Large Custom 3 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_custom_4_delivery_fee',
                'setting_title' => 'X-Large Custom 4 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_custom_1_delivery_fee',
                'setting_title' => '2X-Large Custom 1 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_custom_2_delivery_fee',
                'setting_title' => '2X-Large Custom 2 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_custom_3_delivery_fee',
                'setting_title' => '2X-Large Custom 3 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_custom_4_delivery_fee',
                'setting_title' => '2X-Large Custom 4 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_custom_1_delivery_fee',
                'setting_title' => 'Commercial Custom 1 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_custom_2_delivery_fee',
                'setting_title' => 'Commercial Custom 2 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_custom_3_delivery_fee',
                'setting_title' => 'Commercial Custom 3 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_custom_4_delivery_fee',
                'setting_title' => 'Commercial Custom 4 Delivery Fee',
                'sort_order' => $sortOrder++,
            ],

            //--- Track Insurance ---
            [
                'value_type' => 'number',
                'setting_name' => 'small_daily_track_insurance_fee',
                'setting_title' => 'Small Daily Track Insurance Fee',
                'default_value' => 9,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_daily_track_insurance_fee',
                'setting_title' => 'Medium Daily Track Insurance Fee',
                'default_value' => 18,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_daily_track_insurance_fee',
                'setting_title' => 'Large Daily Track Insurance Fee',
                'default_value' => 24,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_daily_track_insurance_fee',
                'setting_title' => 'X-Large Daily Track Insurance Fee',
                'default_value' => 29,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_daily_track_insurance_fee',
                'setting_title' => '2X-Large Daily Track Insurance Fee',
                'default_value' => 34,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_daily_track_insurance_fee',
                'setting_title' => 'Commercial Daily Track Insurance Fee',
                'default_value' => 67,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'small_weekend_track_insurance_fee',
                'setting_title' => 'Small Weekend Track Insurance Fee',
                'default_value' => 12,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_weekend_track_insurance_fee',
                'setting_title' => 'Medium Weekend Track Insurance Fee',
                'default_value' => 24,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_weekend_track_insurance_fee',
                'setting_title' => 'Large Weekend Track Insurance Fee',
                'default_value' => 36,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_weekend_track_insurance_fee',
                'setting_title' => 'X-Large Weekend Track Insurance Fee',
                'default_value' => 39,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_weekend_track_insurance_fee',
                'setting_title' => '2X-Large Weekend Track Insurance Fee',
                'default_value' => 47,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_weekend_track_insurance_fee',
                'setting_title' => 'Commercial Weekend Track Insurance Fee',
                'default_value' => 97,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'small_weekly_track_insurance_fee',
                'setting_title' => 'Small Weekly Track Insurance Fee',
                'default_value' => 19,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_weekly_track_insurance_fee',
                'setting_title' => 'Medium Weekly Track Insurance Fee',
                'default_value' => 36,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_weekly_track_insurance_fee',
                'setting_title' => 'Large Weekly Track Insurance Fee',
                'default_value' => 48,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_weekly_track_insurance_fee',
                'setting_title' => 'X-Large Weekly Track Insurance Fee',
                'default_value' => 57,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_weekly_track_insurance_fee',
                'setting_title' => '2X-Large Weekly Track Insurance Fee',
                'default_value' => 67,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_weekly_track_insurance_fee',
                'setting_title' => 'Commercial Weekly Track Insurance Fee',
                'default_value' => 134,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'small_monthly_track_insurance_fee',
                'setting_title' => 'Small Monthly Track Insurance Fee',
                'default_value' => 27,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_monthly_track_insurance_fee',
                'setting_title' => 'Medium Monthly Track Insurance Fee',
                'default_value' => 54,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_monthly_track_insurance_fee',
                'setting_title' => 'Large Monthly Track Insurance Fee',
                'default_value' => 72,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_monthly_track_insurance_fee',
                'setting_title' => 'X-Large Monthly Track Insurance Fee',
                'default_value' => 57,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_monthly_track_insurance_fee',
                'setting_title' => '2X-Large Monthly Track Insurance Fee',
                'default_value' => 97,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_monthly_track_insurance_fee',
                'setting_title' => 'Commercial Monthly Track Insurance Fee',
                'default_value' => 197,
                'sort_order' => $sortOrder++,
            ],

            //--- Tire Insurance ---
            [
                'value_type' => 'number',
                'setting_name' => 'small_daily_tire_insurance_fee',
                'setting_title' => 'Small Daily Tire Insurance Fee',
                'default_value' => 9,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_daily_tire_insurance_fee',
                'setting_title' => 'Medium Daily Tire Insurance Fee',
                'default_value' => 18,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_daily_tire_insurance_fee',
                'setting_title' => 'Large Daily Tire Insurance Fee',
                'default_value' => 24,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_daily_tire_insurance_fee',
                'setting_title' => 'X-Large Daily Tire Insurance Fee',
                'default_value' => 29,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_daily_tire_insurance_fee',
                'setting_title' => '2X-Large Daily Tire Insurance Fee',
                'default_value' => 34,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_daily_tire_insurance_fee',
                'setting_title' => 'Commercial Daily Tire Insurance Fee',
                'default_value' => 67,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'small_weekend_tire_insurance_fee',
                'setting_title' => 'Small Weekend Tire Insurance Fee',
                'default_value' => 12,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_weekend_tire_insurance_fee',
                'setting_title' => 'Medium Weekend Tire Insurance Fee',
                'default_value' => 24,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_weekend_tire_insurance_fee',
                'setting_title' => 'Large Weekend Tire Insurance Fee',
                'default_value' => 36,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_weekend_tire_insurance_fee',
                'setting_title' => 'X-Large Weekend Tire Insurance Fee',
                'default_value' => 39,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_weekend_tire_insurance_fee',
                'setting_title' => '2X-Large Weekend Tire Insurance Fee',
                'default_value' => 47,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_weekend_tire_insurance_fee',
                'setting_title' => 'Commercial Weekend Tire Insurance Fee',
                'default_value' => 97,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'small_weekly_tire_insurance_fee',
                'setting_title' => 'Small Weekly Tire Insurance Fee',
                'default_value' => 19,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_weekly_tire_insurance_fee',
                'setting_title' => 'Medium Weekly Tire Insurance Fee',
                'default_value' => 36,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_weekly_tire_insurance_fee',
                'setting_title' => 'Large Weekly Tire Insurance Fee',
                'default_value' => 48,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_weekly_tire_insurance_fee',
                'setting_title' => 'X-Large Weekly Tire Insurance Fee',
                'default_value' => 57,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_weekly_tire_insurance_fee',
                'setting_title' => '2X-Large Weekly Tire Insurance Fee',
                'default_value' => 67,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_weekly_tire_insurance_fee',
                'setting_title' => 'Commercial Weekly Tire Insurance Fee',
                'default_value' => 134,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'small_monthly_tire_insurance_fee',
                'setting_title' => 'Small Monthly Tire Insurance Fee',
                'default_value' => 27,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'medium_monthly_tire_insurance_fee',
                'setting_title' => 'Medium Monthly Tire Insurance Fee',
                'default_value' => 54,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'large_monthly_tire_insurance_fee',
                'setting_title' => 'Large Monthly Tire Insurance Fee',
                'default_value' => 72,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'x_large_monthly_tire_insurance_fee',
                'setting_title' => 'X-Large Monthly Tire Insurance Fee',
                'default_value' => 57,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => '2x_large_monthly_tire_insurance_fee',
                'setting_title' => '2X-Large Monthly Tire Insurance Fee',
                'default_value' => 97,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'number',
                'setting_name' => 'commercial_monthly_tire_insurance_fee',
                'setting_title' => 'Commercial Monthly Tire Insurance Fee',
                'default_value' => 197,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'textarea',
                'setting_name' => 'tire_insurance_info',
                'setting_title' => 'Tire Insurance Message',
                'placeholder' => 'Enter message for tire insurance popup...',
                'default_value' => '<p>By removing Tire Insurance, you accept <strong>full financial responsibility</strong> for any tire damage during your rental (e.g., service call, labor, travel, and parts).</p><p><br>If a tire fails due to a defective component, it is covered automatically; however, this is uncommon.</p><p><br><em>*Covers 1 Tire service per rental period</em></p>',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'tire_insurance_decline_label',
                'setting_title' => 'Decline Button Label',
                'placeholder' => 'Enter decline button text',
                'default_value' => "I'll Take The Risk",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'tire_insurance_approve_label',
                'setting_title' => 'Accept Button Label',
                'placeholder' => 'Enter accept button text',
                'default_value' => "Yes, Add Tire Insurance",
                'sort_order' => $sortOrder++,
            ],


            // --- Prepaid Fuel ---
            [
                'value_type' => 'textarea',
                'setting_name' => 'prepaid_fuel_info',
                'setting_title' => 'Prepaid Fuel Message',
                'placeholder' => 'Enter message for prepaid fuel popup...',
                'default_value' => "<p>The machine is delivered full and must be returned full. If it isn&rsquo;t, <strong>fuel charges plus a service fee</strong> (to cover time and handling) will apply.</p><p><br>Choosing Prepaid Fuel removes the need to refill at return.</p>",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'prepaid_fuel_decline_label',
                'setting_title' => 'Decline Button Label',
                'placeholder' => 'Enter decline button text',
                'default_value' => "I'll Fill It Up",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'prepaid_fuel_approve_label',
                'setting_title' => 'Accept Button Label',
                'placeholder' => 'Enter accept button text',
                'default_value' => "Fill It For Me",
                'sort_order' => $sortOrder++,
            ],
            // --- Prepaid Cleaning ---

            [
                'value_type' => 'textarea',
                'setting_name' => 'prepaid_cleaning_info',
                'setting_title' => 'Prepaid Cleaning Message',
                'placeholder' => 'Enter message for prepaid cleaning popup...',
                'default_value' => "<p>I&rsquo;m responsible for returning the equipment clean and in the same condition as received; otherwise, cleaning charges apply.</p><p><br>The cleaning option covers Standard cleaning only (light dirt/debris). Extreme cleaning (e.g., heavy mud, interior spills, trash/odor removal) is billed separately.</p>",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'prepaid_cleaning_decline_label',
                'setting_title' => 'Decline Button Label',
                'placeholder' => 'Enter decline button text',
                'default_value' => "I'll Clean It",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'prepaid_cleaning_approve_label',
                'setting_title' => 'Accept Button Label',
                'placeholder' => 'Enter accept button text',
                'default_value' => "Clean It For Me",
                'sort_order' => $sortOrder++,
            ],

            // --- Track Insurance ---
            [
                'value_type' => 'textarea',
                'setting_name' => 'track_insurance_info',
                'setting_title' => 'Thrown Track Insurance Message',
                'placeholder' => 'Enter message for thrown track insurance popup...',
                'default_value' => '<p>By removing Track Insurance, you accept <strong>full financial responsibility</strong> for any thrown track during your rental (e.g., service call, labor, travel, and parts).</p><p><br>If a track comes off due to a defective or excessively worn component, it is covered automatically; however, this is uncommon.</p><p><br><em>*Covers 1 Thrown Track service per rental period</em></p>',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'track_insurance_decline_label',
                'setting_title' => 'Decline Button Label',
                'placeholder' => 'Enter decline button text',
                'default_value' => "I'll Take The Risk",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'track_insurance_approve_label',
                'setting_title' => 'Accept Button Label',
                'placeholder' => 'Enter accept button text',
                'default_value' => "Keep The Added Protection",
                'sort_order' => $sortOrder++,
            ],

            // --- Damage Waiver ---
            [
                'value_type' => 'textarea',
                'setting_name' => 'damage_waiver_info',
                'setting_title' => 'Damage Waiver Protection Message',
                'placeholder' => 'Enter message for damage waiver protection popup...',
                'default_value' => '<p>By removing the Damage Waiver, you accept <strong>full financial responsibility</strong> for any damage or repairs (e.g., parts, labor, service calls, and transport).</p>',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'damage_waiver_decline_label',
                'setting_title' => 'Decline Button Label',
                'placeholder' => 'Enter decline button text',
                'default_value' => "I'll Take The Risk",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'damage_waiver_approve_label',
                'setting_title' => 'Accept Button Label',
                'placeholder' => 'Enter accept button text',
                'default_value' => "Keep The Added Protection",
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

    private function addCompanySettings()
    {
        $sortOrder = 1;

        // Company identity used system-wide (Document Generator merge codes).
        // Rent 'n King values are tenant defaults, never framework code.
        $this->settings['Company Settings'] = [
            [
                'value_type' => 'text',
                'setting_name' => 'company_name',
                'setting_title' => 'Company Display Name',
                'placeholder' => 'Company name shown on documents and branding',
                'default_value' => "Rent 'n King",
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'main_url',
                'setting_title' => 'Main URL / Website',
                'placeholder' => 'Customer-facing website, e.g. YourCompany.com',
                'default_value' => 'RentnKing.com',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'company_main_phone',
                'setting_title' => 'Main Phone Number',
                'placeholder' => 'Primary company phone number',
                'default_value' => '(615) 815-6734',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'company_sales_phone',
                'setting_title' => 'Sales Phone Number (optional)',
                'placeholder' => 'Dedicated sales line, if different from main phone',
                'default_value' => '',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'textarea',
                'setting_name' => 'store_hours_fallback',
                'setting_title' => 'Store Hours (fallback text)',
                'placeholder' => 'One line per range, used when a store has no structured hours of operation',
                'default_value' => "Monday–Friday: 7:00 AM–5:00 PM\nSaturday: 7:00 AM–12:00 PM\nSunday: Closed",
                'sort_order' => $sortOrder++,
            ],
        ];
    }

    private function addPriceListSettings()
    {
        $sortOrder = 1;

        // Customer Price List document text — edited from Products →
        // Price List → Document Text (not the System Configuration page).
        // Merge codes ({{ main_url }}, {{ company_name }}, …) resolve at
        // generation time via DocumentMergeCodes.
        $this->settings['Price List Settings'] = [
            [
                'value_type' => 'text',
                'setting_name' => 'price_list_title',
                'setting_title' => 'Price List — Document Title',
                'placeholder' => 'Title printed in the document header',
                'default_value' => 'Rental Price List',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'textarea',
                'setting_name' => 'price_list_value_message',
                'setting_title' => 'Price List — Value Message',
                'placeholder' => 'Highlighted message on the price list final page (merge codes supported)',
                'default_value' => 'Have a longer project? Ask about weekly and monthly rental options. In many '
                    . 'cases, renting for the week gives you the best value — three days of rental often gets you '
                    . 'seven days of use.',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'textarea',
                'setting_name' => 'price_list_disclaimer',
                'setting_title' => 'Price List — Disclaimer',
                'placeholder' => 'Final-page disclaimer (merge codes supported)',
                'default_value' => 'This price list is provided for general informational purposes only and is intended to help '
                    . 'customers compare rental products and pricing at the time it was generated. Because rental '
                    . 'equipment pricing can vary based on rental duration, delivery location, attachments, accessories, '
                    . 'optional protection plans, taxes, fees, fuel, cleaning, damage, and other rental options, this '
                    . 'printed price list cannot reflect every possible rental scenario.'
                    . "\n\n"
                    . 'Prices are subject to change at any time without notice. The pricing displayed on {{ main_url }} '
                    . 'at the time a reservation is created is the official rental price and supersedes any previously '
                    . 'printed price list. This document is not a quote, estimate, reservation, or price guarantee and '
                    . 'should not be relied upon as the final cost of a rental.'
                    . "\n\n"
                    . 'Final rental charges may vary based on the options selected and the specific details of the '
                    . 'rental. For current pricing, product specifications, photos, and complete rental information, '
                    . 'visit {{ main_url }} or contact {{ company_name }}.',
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
                'placeholder' => 'Example: 10',
                'default_value' => 10,
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
                'setting_title' => 'Admin code',
                'default_value' => 12345678,
                'placeholder' => 'Enter admin code',
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
                'is_encrypted' => 1,
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
                'default_value' => 'Authorize.Net',
                'is_secure_field' => 1,
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'payment_api_public_key',
                'setting_title' => 'Payment API - Public Key',
                'placeholder' => 'Enter public API key',
                'is_secure_field' => 1,
                'is_encrypted' => 1,
                'is_required' => 1,
                'is_eye_toggle' => 1,
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
                'is_required' => 1,
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
                'is_encrypted' => 1,
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
                'is_required' => 1,
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
                'is_encrypted' => 1,
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
                'is_encrypted' => 1,
                'is_required' => 1,
                'is_eye_toggle' => 1,
                'default_value' => '+16157032809',
                'sort_order' => $sortOrder++,
            ],
            [
                'value_type' => 'text',
                'setting_name' => 'twilio_messaging_service_sid',
                'setting_title' => 'Twilio Messaging Service SID',
                'placeholder' => 'Enter Twilio Messaging Service SID',
                'is_secure_field' => 1,
                'is_encrypted' => 1,
                'is_required' => 1,
                'is_eye_toggle' => 1,
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
                'is_encrypted' => 1,
                'is_required' => 1,
                'is_eye_toggle' => 1,
                'default_value' => 'billing@kabba.ai',
                'sort_order'   => $sortOrder++,
            ],
            [
                'value_type'   => 'password',
                'setting_name' => 'mail_password',
                'setting_title' => 'Mail Password',
                'default_value' => '-PP}uX0C%}#8',
                'is_secure_field' => 1,
                'is_encrypted' => 1,
                'is_required' => 1,
                'is_eye_toggle' => 1,
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


    private function addPrivacyPolicySettings()
    {
        $sortOrder = 1;

        $this->settings['Privacy Policy Settings'] = [

            // Title
            [
                'value_type'     => 'text',
                'setting_name'   => 'privacy_policy_title',
                'setting_title'  => 'Privacy Policy Title',
                'default_value'  => 'Privacy Policy',
                'sort_order'     => $sortOrder++,
            ],

            // Status
            [
                'value_type'     => 'checkbox',
                'setting_name'   => 'privacy_policy_status',
                'setting_title'  => 'Enable Privacy Policy',
                'default_value'  => true,
                'sort_order'     => $sortOrder++,
            ],

            // Description (Content)
            [
                'value_type'     => 'textarea',
                'setting_name'   => 'privacy_policy_description',
                'setting_title'  => 'Privacy Policy Description',
                'default_value'  => 'Enter your privacy policy content here...',
                'sort_order'     => $sortOrder++,
            ],
        ];
    }


    private function addTermsConditionsSettings()
    {
        $sortOrder = 1;

        $this->settings['Terms & Conditions Settings'] = [

            // Title
            [
                'value_type'     => 'text',
                'setting_name'   => 'terms_conditions_title',
                'setting_title'  => 'Terms & Conditions Title',
                'default_value'  => 'Terms & Conditions',
                'sort_order'     => $sortOrder++,
            ],

            // Status
            [
                'value_type'     => 'checkbox',
                'setting_name'   => 'terms_conditions_status',
                'setting_title'  => 'Enable Terms & Conditions',
                'default_value'  => true,
                'sort_order'     => $sortOrder++,
            ],

            // Description (Content)
            [
                'value_type'     => 'textarea',
                'setting_name'   => 'terms_conditions_description',
                'setting_title'  => 'Terms & Conditions Description',
                'default_value'  => 'Enter your terms & conditions content here...',
                'sort_order'     => $sortOrder++,
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
        // Never allow updates to existing settings in production, regardless of the flag
        $updateExisting = config('app.seeders.existing_settings_update') && !app()->environment('production');

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

                //  Now trigger mutator (only for newly created settings)
                if ($setting_item->wasRecentlyCreated && isset($setting['default_value'])) {
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
