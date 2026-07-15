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
        // Retired keys no longer seeded (existing DB rows are left untouched):
        //  - top_text / top_phone / bottom_title / bottom_text — legacy
        //    homepage fields with no consumers (structured data reads site_phone)
        //  - all_rights_reserved / powered_by — footer content is owned by the
        //    Footer Builder (copyright_text / powered_by_text)
        $branding = [
            'site_logo' => null,
            'site_phone' => '(615) 815-6734',
            'site_email' => 'rentnking@gmail.com',
            'site_name' => 'Rent `n King',
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