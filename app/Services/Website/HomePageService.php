<?php

namespace App\Services\Website;

use Illuminate\Support\Facades\Cache;
use App\Models\WebsiteManagement\WebsitePage;
use App\Models\Global\Media;
use App\Models\ProductManagement\ProductCategory;

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
            'seo'             => $this->buildSeo($page),
            'orderedSections' => $page->sections->pluck('section_key'),
        ];
    }

    private function buildHero($section): object
    {
        $c = $section?->content ?? [];

        // Text-readability panel (Home Builder → Hero → Text Readability).
        // Backward-compatible: heroes saved before this feature have no keys,
        // so the DOCUMENTED default is an enabled dark medium panel with white
        // text — matching the approved current homepage treatment.
        $style    = in_array($c['text_background_style'] ?? null, ['dark', 'light'], true)
                        ? $c['text_background_style'] : 'dark';
        $strength = in_array($c['text_background_strength'] ?? null, ['light', 'medium', 'strong'], true)
                        ? $c['text_background_strength'] : 'medium';

        return (object) [
            'imageUrl'         => $section?->image_url,
            'title'            => $section?->title ?? "THE RIGHT EQUIPMENT.\nTHE RIGHT SUPPORT.",
            'subtitle'         => $section?->subtitle ?? "Local team. Quality equipment.\nReady when you are.",
            'overlayEnabled'   => (bool) ($c['overlay_enabled'] ?? true),
            'overlayOpacity'   => (int)  ($c['overlay_opacity'] ?? 65),
            'titlePosition'    => $c['title_position']    ?? 'left',
            'subtitlePosition' => $c['subtitle_position'] ?? 'left',
            'titleColor'       => $c['title_color']       ?? '#ffffff',
            'subtitleColor'    => $c['subtitle_color']    ?? '#ffffff',
            'textBgEnabled'    => (bool) ($c['text_background_enabled'] ?? true),
            'textBgStyle'      => $style,
            'textBgStrength'   => $strength,
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

    private function buildFeaturedRentals($section): object
    {
        // Which categories appear here is driven entirely by ProductCategory.is_featured
        // (top-level, published, must have at least one product) — not by section items.
        $categoryIds = ProductCategory::has('products')
            ->published()
            ->whereNull('parent_id')
            ->where('is_featured', 'Yes')
            ->sortOrder()
            ->pluck('id');

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
            // powered-by attribution comes from config('app.powered_by_*'),
            // never from stored website data
            'quickLinks'    => $items->where('item_key', 'quick_link')->sortBy('display_order')->values(),
            'otherLinks'    => $items->where('item_key', 'other_link')->sortBy('display_order')->values(),
            'socialLinks'   => $items->where('item_key', 'social_link')->sortBy('display_order')->values(),
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
            'hero'            => $this->buildHero(null),
            'contactStrip'    => $this->buildContactStrip(null),
            'featuredRentals' => $this->buildFeaturedRentals(null),
            'featureStrip'    => $this->buildFeatureStrip(null),
            'footer'          => $this->buildFooter(null),
            'seo'             => $this->buildSeo(null),
            'orderedSections' => collect(['hero', 'contact_strip', 'featured_rentals', 'feature_strip']),
        ];
    }
}
