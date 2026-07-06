<?php

namespace App\Services\Website;

use Illuminate\Support\Facades\Cache;
use App\Models\WebsiteManagement\WebsitePage;
use App\Models\Global\Media;

class ContactPageService
{
    const CACHE_KEY = 'contact_page_builder_data';
    const CACHE_TTL = 1800; // 30 minutes

    public function getData(): object
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->build());
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function build(): object
    {
        $page = WebsitePage::where('page_key', 'contact_v2')
            ->with([
                'sections'       => fn ($q) => $q->where('status', 'Active')->orderBy('display_order'),
                'sections.items' => fn ($q) => $q->where('status', 'Active')->orderBy('display_order'),
            ])
            ->first();

        if (!$page) {
            return $this->defaults();
        }

        $sections = $page->sections->keyBy('section_key');

        return (object) [
            'hero'         => $this->buildHero($sections->get('hero')),
            'contactStrip' => $this->buildContactStrip($sections->get('contact_strip')),
            'locations'    => $this->buildLocations($sections->get('locations')),
            'questionCta'  => $this->buildQuestionCta($sections->get('question_cta')),
            'featureStrip' => $this->buildFeatureStrip($sections->get('feature_strip')),
            'seo'          => $this->buildSeo($page),
        ];
    }

    private function buildHero($section): object
    {
        $c = $section?->content ?? [];
        return (object) [
            'imageUrl'         => $section?->image_url,
            'title'            => $section?->title ?? 'GET IN TOUCH WITH US',
            'subtitle'         => $section?->subtitle ?? "We're here to help. Call, visit, or send us a message.",
            'overlayEnabled'   => (bool) ($c['overlay_enabled'] ?? true),
            'overlayOpacity'   => (int)  ($c['overlay_opacity'] ?? 65),
            'titlePosition'    => $c['title_position']    ?? 'left',
            'subtitlePosition' => $c['subtitle_position'] ?? 'left',
            'titleColor'       => $c['title_color']       ?? '#ffffff',
            'subtitleColor'    => $c['subtitle_color']    ?? '#ffffff',
        ];
    }

    private function buildContactStrip($section): object
    {
        $items = $section?->items->keyBy('item_key') ?? collect();
        return (object) [
            'title'      => $section?->title ?? 'Call Our Rental Specialists',
            'subtitle'   => $section?->subtitle,
            'phoneCard'  => $items->get('phone_card'),
            'storeCard1' => $items->get('store_1'),
            'storeCard2' => $items->get('store_2'),
            'searchCard' => $items->get('search_card'),
        ];
    }

    private function buildLocations($section): object
    {
        return (object) [
            'title'    => $section?->title ?? 'Our Locations',
            'subtitle' => $section?->subtitle ?? '',
            'items'    => $section?->items ?? collect(),
        ];
    }

    private function buildQuestionCta($section): object
    {
        $c = $section?->content ?? [];
        return (object) [
            'title'       => $section?->title ?? 'Have a Question?',
            'subtitle'    => $section?->subtitle ?? 'Our team is ready to help you find the right equipment for your job.',
            'buttonText'  => $section?->button_text ?? 'Contact Us',
            'buttonUrl'   => $section?->button_url ?? '/contact-us',
            'phoneNumber' => $c['phone_number'] ?? null,
            'icon'        => $c['icon'] ?? null,
        ];
    }

    private function buildFeatureStrip($section): object
    {
        return (object) [
            'items' => $section?->items ?? collect(),
        ];
    }

    private function buildSeo($page): object
    {
        $ogImageUrl = null;
        if ($page?->og_image) {
            $ogImageUrl = Media::find($page->og_image)?->url;
        }
        return (object) [
            'metaTitle'       => $page?->meta_title,
            'metaDescription' => $page?->meta_description,
            'metaKeywords'    => $page?->meta_keywords,
            'ogTitle'         => $page?->og_title,
            'ogDescription'   => $page?->og_description,
            'ogImage'         => $ogImageUrl,
            'canonicalUrl'    => $page?->canonical_url,
        ];
    }

    private function defaults(): object
    {
        return (object) [
            'hero'         => $this->buildHero(null),
            'contactStrip' => $this->buildContactStrip(null),
            'locations'    => $this->buildLocations(null),
            'questionCta'  => $this->buildQuestionCta(null),
            'featureStrip' => $this->buildFeatureStrip(null),
            'seo'          => $this->buildSeo(null),
        ];
    }
}
