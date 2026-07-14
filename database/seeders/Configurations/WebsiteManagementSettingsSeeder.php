<?php

namespace Database\Seeders\Configurations;

use Illuminate\Database\Seeder;
use App\Models\Configurations\Setting;
use App\Models\Global\Media;

class WebsiteManagementSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $updateExisting = config('app.seeders.existing_settings_update');

        /**
         * ----------------------------------
         * Branding Settings
         * ----------------------------------
         */
        $branding = [
            'site_logo' => null,
            'site_phone' => '(615) 815-6734',
            'site_email' => 'rentnking@gmail.com',
            'site_name' => 'Rent `n King',
            'top_text' => 'Call For Live Assistance from a Real Person:',
            'top_phone' => '(615) 815-6734',
            'bottom_title' => 'About the company',
            'bottom_text' => 'Locally owned and committed to 1st-tier customer service...',
            'all_rights_reserved' => 'Rent `n King',
            'powered_by' => 'Kabba.ai',
        ];

        foreach ($branding as $key => $value) {

            $item = Setting::firstOrCreate([
                'setting_name' => $key,
                'setting_type' => 'Website Management Branding',
            ]);

            // set value on create
            if ($item->wasRecentlyCreated) {
                $item->setting_value = $value;
                $item->save();
            }

            // update only if allowed
            if ($updateExisting && !$item->wasRecentlyCreated) {
                $item->setting_value = $value;
                $item->save();
            }
        }

        /**
         * ----------------------------------
         * Contact Us Section
         * ----------------------------------
         */
        $contactUs = [
            'contact_title' => 'Speak with a human – No frustrating menus and bots',
            'contact_subtitle' => '(We might be on the phone...)',
        ];

        foreach ($contactUs as $key => $value) {

            $item = Setting::firstOrCreate([
                'setting_name' => $key,
                'setting_type' => 'Website Management Contact Us Section',
            ]);

            if ($item->wasRecentlyCreated) {
                $item->setting_value = $value;
                $item->save();
            }

            if ($updateExisting && !$item->wasRecentlyCreated) {
                $item->setting_value = $value;
                $item->save();
            }
        }

        /**
         * ----------------------------------
         * Default Site Logo
         * ----------------------------------
         */
        $logo = Media::firstOrCreate(
            ['original_file_name' => 'logo.png'],
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

        $item = Setting::firstOrCreate([
            'setting_name' => 'site_logo',
            'setting_type' => 'Website Management Branding',
        ]);

        if ($item->wasRecentlyCreated || $updateExisting) {
            $item->setting_value = $logo->id;
            $item->save();
        }
    }
}