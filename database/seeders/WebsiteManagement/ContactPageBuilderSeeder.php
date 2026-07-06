<?php

namespace Database\Seeders\WebsiteManagement;

use Illuminate\Database\Seeder;
use App\Models\WebsiteManagement\WebsitePage;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Models\WebsiteManagement\WebsiteSectionItem;

class ContactPageBuilderSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Contact V2 page record ─────────────────────────────────────────
        $page = WebsitePage::updateOrCreate(
            ['page_key' => 'contact_v2'],
            [
                'title'            => 'Contact Us',
                'slug'             => 'contact-us-v2',
                'status'           => 'Active',
                'meta_title'       => 'Contact Us – Rent n King',
                'meta_description' => 'Get in touch with Rent n King. Find our store locations, hours, phone numbers, and more.',
                'meta_keywords'    => 'contact, rental store, locations, phone, hours, rent n king',
                'og_title'         => 'Contact Us – Rent n King',
                'og_description'   => 'Visit or call your nearest Rent n King location. We\'re here to help.',
            ]
        );

        // ── 2. Sections ───────────────────────────────────────────────────────
        $sections = [
            [
                'section_key'  => 'hero',
                'section_name' => 'Hero Banner',
                'display_order' => 1,
                'status'        => 'Active',
            ],
            [
                'section_key'  => 'contact_strip',
                'section_name' => 'Contact Strip',
                'display_order' => 2,
                'status'        => 'Active',
            ],
            [
                'section_key'  => 'locations',
                'section_name' => 'Store Locations',
                'display_order' => 3,
                'status'        => 'Active',
            ],
            [
                'section_key'  => 'question_cta',
                'section_name' => 'Have a Question?',
                'display_order' => 4,
                'status'        => 'Active',
            ],
            [
                'section_key'  => 'feature_strip',
                'section_name' => 'Feature Strip',
                'display_order' => 5,
                'status'        => 'Active',
            ],
        ];

        foreach ($sections as $data) {
            $section = WebsitePageSection::updateOrCreate(
                [
                    'website_page_id' => $page->id,
                    'section_key'     => $data['section_key'],
                ],
                array_merge($data, ['website_page_id' => $page->id])
            );

            if ($data['section_key'] === 'contact_strip') {
                $this->seedContactStripItems($section);
            }
        }

        $this->command->info('Contact Page Builder seeded successfully.');
    }

    private function seedContactStripItems(WebsitePageSection $section): void
    {
        $items = [
            [
                'item_key'      => 'phone_card',
                'title'         => 'Main Sales Line',
                'subtitle'      => '',
                'description'   => "Questions? We're here to help!",
                'icon'          => 'heroicon-s-phone',
                'display_order' => 1,
                'status'        => 'Active',
            ],
            [
                'item_key'      => 'store_1',
                'title'         => 'Store Location 1',
                'subtitle'      => '',
                'description'   => 'Visit your nearest store.',
                'icon'          => 'heroicon-s-map-pin',
                'display_order' => 2,
                'status'        => 'Active',
            ],
            [
                'item_key'      => 'store_2',
                'title'         => 'Store Location 2',
                'subtitle'      => '',
                'description'   => 'Visit your nearest store.',
                'icon'          => 'heroicon-s-map-pin',
                'display_order' => 3,
                'status'        => 'Active',
            ],
        ];

        foreach ($items as $item) {
            WebsiteSectionItem::firstOrCreate(
                [
                    'website_page_section_id' => $section->id,
                    'item_key'                => $item['item_key'],
                ],
                array_merge($item, ['website_page_section_id' => $section->id])
            );
        }
    }
}
