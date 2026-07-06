<?php

use App\Models\Configurations\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Company Settings group + editable Document Generator text blocks.
 *
 * The Rent 'n King values below are TENANT DATA for the current install —
 * framework/document code reads these settings (via DocumentMergeCodes) and
 * must never hardcode them. Other rental companies change these in
 * Settings → System Configuration → Company.
 */
return new class extends Migration
{
    private const TYPE = 'Company Settings';

    public function up(): void
    {
        $sortOrder = 1;

        $settings = [
            [
                'setting_name'  => 'company_name',
                'setting_title' => 'Company Display Name',
                'value_type'    => 'text',
                'setting_value' => "Rent 'n King",
                'placeholder'   => 'Company name shown on documents and branding',
            ],
            [
                'setting_name'  => 'main_url',
                'setting_title' => 'Main URL / Website',
                'value_type'    => 'text',
                'setting_value' => 'RentnKing.com',
                'placeholder'   => 'Customer-facing website, e.g. YourCompany.com',
            ],
            [
                'setting_name'  => 'company_main_phone',
                'setting_title' => 'Main Phone Number',
                'value_type'    => 'text',
                'setting_value' => '(615) 815-6734',
                'placeholder'   => 'Primary company phone number',
            ],
            [
                'setting_name'  => 'company_sales_phone',
                'setting_title' => 'Sales Phone Number (optional)',
                'value_type'    => 'text',
                'setting_value' => '',
                'placeholder'   => 'Dedicated sales line, if different from main phone',
            ],
            [
                'setting_name'  => 'store_hours_fallback',
                'setting_title' => 'Store Hours (fallback text)',
                'value_type'    => 'textarea',
                'setting_value' => "Monday–Friday: 7:00 AM–5:00 PM\nSaturday: 7:00 AM–12:00 PM\nSunday: Closed",
                'placeholder'   => 'One line per range, used when a store has no structured hours of operation',
            ],
            [
                'setting_name'  => 'price_list_value_message',
                'setting_title' => 'Price List — Value Message',
                'value_type'    => 'textarea',
                'setting_value' => 'Have a longer project? Ask about weekly and monthly rental options. In many '
                    . 'cases, renting for the week gives you the best value — three days of rental often gets you '
                    . 'seven days of use.',
                'placeholder'   => 'Highlighted message on the price list final page (merge codes supported)',
            ],
            [
                'setting_name'  => 'price_list_disclaimer',
                'setting_title' => 'Price List — Disclaimer',
                'value_type'    => 'textarea',
                'setting_value' => 'This price list is provided for general informational purposes only and is intended to help '
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
                'placeholder'   => 'Final-page disclaimer (merge codes supported)',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['setting_name' => $setting['setting_name']],
                [
                    'setting_type'    => self::TYPE,
                    'setting_title'   => $setting['setting_title'],
                    'value_type'      => $setting['value_type'],
                    'setting_value'   => $setting['setting_value'],
                    'placeholder'     => $setting['placeholder'],
                    'sort_order'      => $sortOrder++,
                    'is_secure_field' => 0,
                    'is_encrypted'    => 0,
                ]
            );
        }
    }

    public function down(): void
    {
        Setting::where('setting_type', self::TYPE)
            ->whereIn('setting_name', [
                'company_name', 'main_url', 'company_main_phone', 'company_sales_phone',
                'store_hours_fallback', 'price_list_value_message', 'price_list_disclaimer',
            ])
            ->delete();
    }
};
