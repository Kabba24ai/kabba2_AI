<?php

namespace App\Services\Website;

use Illuminate\Support\Facades\Cache;
use App\Models\WebsiteManagement\WebsitePage;

class HomePageService
{
    const CACHE_KEY = 'homepage_builder_data';
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
        $page = WebsitePage::where('page_key', 'home')
            ->with([
                'sections' => fn ($q) => $q->where('status', 'Active')->orderBy('display_order'),
                'sections.items' => fn ($q) => $q->where('status', 'Active')->orderBy('display_order'),
            ])
            ->first();

        if (!$page) {
            return $this->defaults();
        }

        $sections = $page->sections->keyBy('section_key');

        return (object) [
            'hero'            => $this->buildHero($sections->get('hero')),
            'contactStrip'    => $this->buildContactStrip($sections->get('contact_strip')),
            'featuredRentals' => $this->buildFeaturedRentals($sections->get('featured_rentals')),
            'featureStrip'    => $this->buildFeatureStrip($sections->get('feature_strip')),
            'footer'          => $this->buildFooter($sections->get('footer')),
            'orderedSections' => $page->sections->pluck('section_key'),
        ];
    }

    private function buildHero($section): object
    {
        $c = $section?->content ?? [];
        return (object) [
            'imageUrl'       => $section?->image_url,
            'title'          => $section?->title ?? "THE RIGHT EQUIPMENT.\nTHE RIGHT SUPPORT.",
            'subtitle'       => $section?->subtitle ?? "Local team. Quality equipment.\nReady when you are.",
            'overlayEnabled' => (bool) ($c['overlay_enabled'] ?? true),
            'overlayOpacity' => (int) ($c['overlay_opacity'] ?? 65),
            'buttonEnabled'  => (bool) ($c['button_enabled'] ?? false),
            'buttonText'     => $c['button_text'] ?? 'Browse Equipment',
            'buttonUrl'      => $c['button_url'] ?? '#',
        ];
    }

    private function buildContactStrip($section): object
    {
        $items = $section?->items->keyBy('item_key') ?? collect();
        return (object) [
            'title'      => $section?->title ?? 'Call Our Rental Specialists',
            'subtitle'   => $section?->subtitle ?? 'Need Help Finding The Right Equipment?',
            'phoneCard'  => $items->get('phone_card'),
            'storeCard1' => $items->get('store_1'),
            'storeCard2' => $items->get('store_2'),
            'searchCard' => $items->get('search_card'),
        ];
    }

    private function buildFeaturedRentals($section): object
    {
        $categoryIds = collect();
        if ($section) {
            $categoryIds = $section->items
                ->map(fn ($i) => data_get($i->content, 'category_id'))
                ->filter()
                ->values();
        }
        return (object) [
            'title'       => $section?->title ?? 'FEATURED RENTALS',
            'categoryIds' => $categoryIds,
        ];
    }

    private function buildFeatureStrip($section): object
    {
        return (object) [
            'items' => $section?->items ?? collect(),
        ];
    }

    private function buildFooter($section): object
    {
        $c     = $section?->content ?? [];
        $items = $section?->items ?? collect();
        return (object) [
            'copyrightText' => $c['copyright_text'] ?? null,
            'poweredByText' => $c['powered_by_text'] ?? null,
            'quickLinks'    => $items->where('item_key', 'quick_link')->sortBy('display_order')->values(),
            'otherLinks'    => $items->where('item_key', 'other_link')->sortBy('display_order')->values(),
            'socialLinks'   => $items->where('item_key', 'social_link')->sortBy('display_order')->values(),
        ];
    }

    private function defaults(): object
    {
        return (object) [
            'hero'            => $this->buildHero(null),
            'contactStrip'    => $this->buildContactStrip(null),
            'featuredRentals' => $this->buildFeaturedRentals(null),
            'featureStrip'    => $this->buildFeatureStrip(null),
            'footer'          => $this->buildFooter(null),
            'orderedSections' => collect(['hero', 'contact_strip', 'featured_rentals', 'feature_strip']),
        ];
    }
}
