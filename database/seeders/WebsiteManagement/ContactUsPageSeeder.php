<?php

namespace Database\Seeders\WebsiteManagement;

use Illuminate\Database\Seeder;
use App\Models\WebsiteManagement\WebsitePage;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Models\WebsiteManagement\WebsiteSectionItem;
use App\Services\Website\ComponentRegistry;

class ContactUsPageSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Ensure the V2 page exists (separate from the legacy contact page) ──
        $page = WebsitePage::firstOrCreate(
            ['page_key' => 'contact_v2'],
            [
                'title'           => 'Contact Us V2',
                'slug'            => 'contact-us-v2',
                'status'          => 'Active',
                'publish_status'  => 'published',
                'meta_title'      => 'Contact Us | Rent\'n King',
                'meta_description'=> 'Get in touch with our rental specialists. Call or visit one of our locations in Tennessee.',
                'header_message'  => 'CONTACT US',
                'header_highlight'=> 'REAL PEOPLE. REAL SUPPORT.',
                'header_callout'  => 'We might be on the phone with others when you call, but we\'ll always call you back as quickly as possible.',
            ]
        );

        // Update header fields if the page already existed
        $page->update([
            'header_message'  => 'CONTACT US',
            'header_highlight'=> 'REAL PEOPLE. REAL SUPPORT.',
            'header_callout'  => 'We might be on the phone with others when you call, but we\'ll always call you back as quickly as possible.',
        ]);

        // ── 2. Sections ──────────────────────────────────────────────
        $sections = [
            [
                'key'           => 'hero',
                'name'          => 'Hero Banner',
                'display_order' => 1,
                'title'         => 'CONTACT US',
                'subtitle'      => 'REAL PEOPLE. REAL SUPPORT.',
                'content'       => [
                    'overlay_enabled' => true,
                    'overlay_opacity' => 55,
                    'button_enabled'  => false,
                    'min_height'      => '380px',
                ],
            ],
            [
                'key'           => 'contact_strip',
                'name'          => 'Contact Strip',
                'display_order' => 2,
                'title'         => 'Call Our Rental Specialists',
                'subtitle'      => 'Need help finding the right equipment?',
                'content'       => [],
            ],
            [
                'key'           => 'locations',
                'name'          => 'Locations',
                'display_order' => 3,
                'title'         => 'Our Locations',
                'subtitle'      => '',
                'content'       => [],
            ],
            [
                'key'           => 'question_cta',
                'name'          => 'Question CTA',
                'display_order' => 4,
                'title'         => 'Have a question?',
                'subtitle'      => 'Our team is ready to help you find the right equipment for your project.',
                'button_text'   => 'Call Main Sales Line',
                'button_url'    => 'tel:+16158156734',
                'content'       => [
                    'icon'         => 'heroicon-o-chat-bubble-left-ellipsis',
                    'phone_number' => '(615) 815-6734',
                ],
            ],
            [
                'key'           => 'feature_strip',
                'name'          => 'Feature Strip',
                'display_order' => 5,
                'title'         => 'Why Choose Us',
                'subtitle'      => '',
                'content'       => [],
            ],
        ];

        foreach ($sections as $sData) {
            $section = WebsitePageSection::firstOrCreate(
                [
                    'website_page_id' => $page->id,
                    'section_key'     => $sData['key'],
                ],
                [
                    'section_type'  => $sData['key'],
                    'section_name'  => $sData['name'],
                    'title'         => $sData['title'] ?? null,
                    'subtitle'      => $sData['subtitle'] ?? null,
                    'button_text'   => $sData['button_text'] ?? null,
                    'button_url'    => $sData['button_url'] ?? null,
                    'content'       => $sData['content'] ?? [],
                    'display_order' => $sData['display_order'],
                    'status'        => 'Active',
                ]
            );

            // Seed items per section
            if ($sData['key'] === 'contact_strip') {
                $this->seedContactStripItems($section);
            }

            if ($sData['key'] === 'feature_strip') {
                $this->seedFeatureStripItems($section);
            }
        }
    }

    private function seedContactStripItems(WebsitePageSection $section): void
    {
        $defaults = [
            [
                'item_key'      => 'phone_card',
                'title'         => 'Main Sales Line',
                'subtitle'      => '(615) 815-6734',
                'description'   => "Questions? We're here to help!",
                'display_order' => 1,
                'status'        => 'Active',
            ],
            [
                'item_key'      => 'store_1',
                'title'         => 'Bon Aqua Location',
                'display_order' => 2,
                'status'        => 'Active',
            ],
            [
                'item_key'      => 'store_2',
                'title'         => 'Waverly Location',
                'display_order' => 3,
                'status'        => 'Active',
            ],
        ];

        foreach ($defaults as $item) {
            WebsiteSectionItem::firstOrCreate(
                ['website_page_section_id' => $section->id, 'item_key' => $item['item_key']],
                $item
            );
        }
    }

    private function seedFeatureStripItems(WebsitePageSection $section): void
    {
        $defaults = [
            ['item_key' => 'feature_well_maintained', 'title' => 'Well Maintained Equipment', 'subtitle' => 'Reliable. Clean. Job Ready.',          'icon' => 'heroicon-o-shield-check', 'display_order' => 1],
            ['item_key' => 'feature_expert_support',  'title' => 'Expert Local Support',       'subtitle' => 'Real people. Real answers.',           'icon' => 'heroicon-o-users',        'display_order' => 2],
            ['item_key' => 'feature_delivery',         'title' => 'Delivery Available',         'subtitle' => 'Fast delivery to your job site.',      'icon' => 'heroicon-o-truck',        'display_order' => 3],
        ];

        foreach ($defaults as $item) {
            WebsiteSectionItem::firstOrCreate(
                ['website_page_section_id' => $section->id, 'item_key' => $item['item_key']],
                array_merge($item, ['status' => 'Active'])
            );
        }
    }
}
