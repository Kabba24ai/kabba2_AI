<?php

namespace Database\Seeders\WebsiteManagement;

use Illuminate\Database\Seeder;
use App\Models\WebsiteManagement\WebsitePage;

class WebsitePagesSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'page_key' => 'about',
                'title'    => 'About Us',
                'slug'     => 'about',
                'status'   => 'Inactive',
            ],
            [
                'page_key' => 'contact',
                'title'    => 'Contact',
                'slug'     => 'contact',
                'status'   => 'Inactive',
            ],
            [
                'page_key' => 'faq',
                'title'    => 'FAQ',
                'slug'     => 'faq',
                'status'   => 'Inactive',
            ],
            [
                'page_key' => 'terms',
                'title'    => 'Terms & Conditions',
                'slug'     => 'terms-and-conditions',
                'status'   => 'Inactive',
            ],
            [
                'page_key' => 'privacy',
                'title'    => 'Privacy Policy',
                'slug'     => 'privacy-policy',
                'status'   => 'Inactive',
            ],
        ];

        foreach ($pages as $data) {
            WebsitePage::firstOrCreate(
                ['page_key' => $data['page_key']],
                $data
            );
        }
    }
}
