<?php


    namespace Database\Seeders\Configurations;

    use Illuminate\Database\Console\Seeds\WithoutModelEvents;
    use Illuminate\Database\Seeder;
    use App\Models\Configurations\OpportunitiesSiteContent;
    use App\Models\Configurations\Setting;
    use App\Models\Global\Media;

class WebsiteManagementSettingsSeeder extends Seeder
{
    public function run(): void
    {

        /**
         * ----------------------------------
         * Branding Settings
         * ----------------------------------
         */
        $branding = [
            'site_logo' => null,
            'home_page_image' => null,

            'site_phone' => '(615) 815-6734',
            'site_email' => 'rentnking@gmail.com',
            'site_name' => 'Rent `n King',

            'top_text' => 'Call For Live Assistance from a Real Person:',
            'top_phone' => '(615) 815-6734',
            'bottom_title' => 'About the company',
            'bottom_text' => 'Locally owned and committed to 1st-tier customer service that encourages long-term, repeat customers. We’re growing fast to serve you better across multiple locations.',
            'all_rights_reserved' => 'Rent `n King',
            'powered_by' => 'Kabba.ai',
        ];

        foreach ($branding as $key => $value) {

            Setting::updateOrCreate(
                [
                    'setting_name' => $key,
                    'setting_type' => 'Website Management Branding',
                ],
                [
                    'setting_value' => $value,
                ]
            );

        }


        /**
         * ----------------------------------
         * Contact Us Section
         * ----------------------------------
         */
        $contactUs = [
            'contact_title' => 'Speak with a human – No frustrating menus and bots',
            'contact_subtitle' => '(We might be on the phone with others when you call, but we’ll always call you back as quickly as possible)',
        ];

        foreach ($contactUs as $key => $value) {

            Setting::updateOrCreate(
                [
                    'setting_name' => $key,
                    'setting_type' => 'Website Management Contact Us Section',
                ],
                [
                    'setting_value' => $value,
                ]
            );

        }


        /**
 * ----------------------------------
 * Default Home Page Banner
 * ----------------------------------
 */
$home_page_image = Media::firstOrCreate(
    [
        'original_file_name' => 'banner.jpg'
    ],
    [
        'asset_type' => 'Public Asset',
        'folder_name' => 'front/images',
        'file_name' => 'banner.jpg',
        'file_extension' => 'jpg',
        'file_type' => 'image',
        'mime_type' => 'image/jpeg',
        'file_size' => 0,
        'is_used' => 'Yes',
    ]
);

Setting::updateOrCreate(
[
    'setting_name' => 'home_page_image',
    'setting_type' => 'Website Management Branding'
],
[
    'setting_value' => $home_page_image->id
]);


/**
 * ----------------------------------
 * Default Site Logo
 * ----------------------------------
 */
$logo = Media::firstOrCreate(
    [
        'original_file_name' => 'logo.png'
    ],
    [
        'asset_type' => 'Public Asset',
        'folder_name' => 'front/images',
        'file_name' => 'logo.png',
        'file_extension' => 'png',
        'file_type' => 'image',
        'mime_type' => 'image/png',
        'file_size' => 0,
        'is_used' => 'Yes',
    ]
);

Setting::updateOrCreate(
[
    'setting_name' => 'site_logo',
    'setting_type' => 'Website Management Branding'
],
[
    'setting_value' => $logo->id
]);

    }
}