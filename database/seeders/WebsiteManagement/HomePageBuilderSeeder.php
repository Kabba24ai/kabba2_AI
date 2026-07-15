<?php

namespace Database\Seeders\WebsiteManagement;

use Illuminate\Database\Seeder;
use App\Models\WebsiteManagement\WebsitePage;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Models\WebsiteManagement\WebsiteSectionItem;

class HomePageBuilderSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Home Page record ───────────────────────────────────────────────
        $page = WebsitePage::firstOrCreate(
            ['page_key' => 'home'],
            [
                'title'    => 'Home Page',
                'slug'     => 'home',
                'status'   => 'Active',
                'meta_title'       => 'Rent n King – Equipment Rentals',
                'meta_description' => 'Rent n King provides quality equipment rentals. Local team. Quality equipment. Ready when you are.',
                'meta_keywords'    => 'equipment rental, heavy machinery, Tennessee, Bon Aqua, Charlotte',
                'og_title'         => 'Rent n King – Equipment Rentals',
                'og_description'   => 'Quality equipment rentals from a local team you can trust.',
            ]
        );

        // ── 2. Sections ───────────────────────────────────────────────────────
        $sections = [
            [
                'section_key'  => 'hero',
                'section_name' => 'Hero Section',
                'title'        => 'THE RIGHT EQUIPMENT. THE RIGHT SUPPORT.',
                'subtitle'     => "Local team. Quality equipment.\nReady when you are.",
                'content'      => [
                    'overlay_enabled' => true,
                    'overlay_opacity' => 65,
                    'button_enabled'  => false,
                    'button_text'     => '',
                    'button_url'      => '',
                ],
                'display_order' => 1,
                'status'        => 'Active',
            ],
            [
                'section_key'  => 'contact_strip',
                'section_name' => 'Contact / Rental Specialist Strip',
                'title'        => 'Call Our Rental Specialists',
                'subtitle'     => 'Need Help Finding The Right Equipment?',
                'content'      => [],
                'display_order' => 2,
                'status'        => 'Active',
            ],
            [
                'section_key'  => 'featured_rentals',
                'section_name' => 'Featured Rentals',
                'title'        => 'FEATURED RENTALS',
                'subtitle'     => '',
                'content'      => [],
                'display_order' => 3,
                'status'        => 'Active',
            ],
            [
                'section_key'  => 'feature_strip',
                'section_name' => 'Feature Strip',
                'title'        => '',
                'subtitle'     => '',
                'content'      => [],
                'display_order' => 4,
                'status'        => 'Active',
            ],
            [
                'section_key'  => 'footer',
                'section_name' => 'Footer',
                'title'        => '',
                'subtitle'     => '',
                'content'      => [
                    'copyright_text' => "© " . date('Y') . " Rent 'n King. All rights reserved.",
                ],
                'display_order' => 6,
                'status'        => 'Active',
            ],
        ];

        foreach ($sections as $sectionData) {
            $section = WebsitePageSection::firstOrCreate(
                [
                    'website_page_id' => $page->id,
                    'section_key'     => $sectionData['section_key'],
                ],
                array_merge($sectionData, ['website_page_id' => $page->id])
            );

            // ── 3. Section Items ───────────────────────────────────────────────
            match ($sectionData['section_key']) {
                'contact_strip' => $this->seedContactStripItems($section),
                'feature_strip' => $this->seedFeatureStripItems($section),
                'footer'        => $this->seedFooterItems($section),
                default         => null,
            };
        }

        $this->command->info('Home Page Builder seeded successfully.');
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
                'button_text'   => '',
                'button_url'    => '',
                'display_order' => 1,
                'status'        => 'Active',
            ],
            [
                'item_key'      => 'store_1',
                'title'         => 'Store Location 1',
                'subtitle'      => '',
                'description'   => 'Visit your nearest store.',
                'icon'          => 'heroicon-s-map-pin',
                'button_text'   => 'View Store',
                'button_url'    => '',
                'display_order' => 2,
                'status'        => 'Active',
            ],
            [
                'item_key'      => 'store_2',
                'title'         => 'Store Location 2',
                'subtitle'      => '',
                'description'   => 'Visit your nearest store.',
                'icon'          => 'heroicon-s-map-pin',
                'button_text'   => 'View Store',
                'button_url'    => '',
                'display_order' => 3,
                'status'        => 'Active',
            ],
            [
                'item_key'      => 'search_card',
                'title'         => 'Search Equipment',
                'subtitle'      => 'Find What You Need',
                'description'   => 'Browse our full inventory and reserve online.',
                'icon'          => 'heroicon-o-magnifying-glass',
                'button_text'   => 'Search',
                'button_url'    => '#',
                'display_order' => 4,
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

    private function seedFeatureStripItems(WebsitePageSection $section): void
    {
        $items = [
            [
                'item_key'      => 'well_maintained',
                'title'         => 'Well Maintained Equipment',
                'subtitle'      => 'Reliable. Clean. Job Ready.',
                'icon'          => 'heroicon-o-shield-check',
                'display_order' => 1,
                'status'        => 'Active',
            ],
            [
                'item_key'      => 'expert_support',
                'title'         => 'Expert Local Support',
                'subtitle'      => 'Real people. Real answers.',
                'icon'          => 'fa-headset',
                'display_order' => 2,
                'status'        => 'Active',
            ],
            [
                'item_key'      => 'delivery',
                'title'         => 'Delivery Available',
                'subtitle'      => 'Fast delivery to your job site.',
                'icon'          => 'heroicon-o-truck',
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

    private function seedFooterItems(WebsitePageSection $section): void
    {
        $quickLinks = [
            ['title' => 'Home',                'button_url' => '/',                       'display_order' => 1],
            ['title' => 'FAQ',                 'button_url' => '/faqs',                   'display_order' => 2],
            ['title' => 'Contact Us',          'button_url' => '/contact-us',             'display_order' => 3],
        ];

        $otherLinks = [
            ['title' => 'Contact Us',               'button_url' => '/contact-us',             'display_order' => 1],
            ['title' => 'Terms & Conditions',        'button_url' => '/terms-and-conditions',   'display_order' => 2],
            ['title' => 'Privacy Policy',            'button_url' => '/privacy-policy',         'display_order' => 3],
            ['title' => 'Employment Opportunities',  'button_url' => config('app.domains.opportunities', '#'), 'display_order' => 4],
        ];

        $socialLinks = [
            ['title' => 'Facebook',  'button_url' => '#', 'icon' => 'fa-facebook',  'display_order' => 1],
            ['title' => 'Instagram', 'button_url' => '#', 'icon' => 'fa-instagram', 'display_order' => 2],
        ];

        foreach ($quickLinks as $link) {
            WebsiteSectionItem::firstOrCreate(
                [
                    'website_page_section_id' => $section->id,
                    'item_key'                => 'quick_link',
                    'title'                   => $link['title'],
                ],
                array_merge($link, [
                    'website_page_section_id' => $section->id,
                    'item_key'  => 'quick_link',
                    'status'    => 'Active',
                ])
            );
        }

        foreach ($otherLinks as $link) {
            WebsiteSectionItem::firstOrCreate(
                [
                    'website_page_section_id' => $section->id,
                    'item_key'                => 'other_link',
                    'title'                   => $link['title'],
                ],
                array_merge($link, [
                    'website_page_section_id' => $section->id,
                    'item_key' => 'other_link',
                    'status'   => 'Active',
                ])
            );
        }

        foreach ($socialLinks as $link) {
            WebsiteSectionItem::firstOrCreate(
                [
                    'website_page_section_id' => $section->id,
                    'item_key'                => 'social_link',
                    'title'                   => $link['title'],
                ],
                array_merge($link, [
                    'website_page_section_id' => $section->id,
                    'item_key' => 'social_link',
                    'status'   => 'Active',
                ])
            );
        }
    }
}
