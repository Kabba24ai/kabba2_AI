<?php

namespace App\Services\AIVisibility;

use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use App\Models\Configurations\Setting;
use Illuminate\Support\Collection;

class SchemaBuilder
{
    // ─────────────────────────────────────────────────────────────────────────
    //  Product schema
    // ─────────────────────────────────────────────────────────────────────────

    public function buildProductSchema(Product $product): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'Product',
            'name'     => $product->product_name,
        ];

        $description = $product->seo_description
            ?: strip_tags($product->short_description ?? '')
            ?: strip_tags($product->description ?? '');
        if ($description) {
            $schema['description'] = substr(trim($description), 0, 500);
        }

        // Image from primary media
        $imageUrl = $this->resolveProductImage($product);
        if ($imageUrl) {
            $schema['image'] = $imageUrl;
        }

        // SKU / identifier
        if (!empty($product->sku)) {
            $schema['sku'] = $product->sku;
        }
        if (!empty($product->barcode)) {
            $schema['gtin'] = $product->barcode;
        }

        // URL
        $schema['url'] = $this->buildProductUrl($product);

        // Offers — rental or retail
        $offers = $this->buildOffers($product);
        if (!empty($offers)) {
            $schema['offers'] = count($offers) === 1 ? $offers[0] : $offers;
        }

        // Brand / seller from settings
        $brand = $this->resolveBrandName();
        if ($brand) {
            $schema['brand'] = ['@type' => 'Brand', 'name' => $brand];
        }

        return $schema;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Offer schemas (rental periods + retail)
    // ─────────────────────────────────────────────────────────────────────────

    public function buildOffers(Product $product): array
    {
        $offers  = [];
        $seller  = $this->buildSellerNode();
        $url     = $this->buildProductUrl($product);

        if (in_array($product->product_type, ['Rental', 'Both'])) {
            $periods = [
                'daily'   => ['field' => 'rental_daily',   'label' => 'Day'],
                'weekend' => ['field' => 'rental_weekend',  'label' => 'Weekend'],
                'weekly'  => ['field' => 'rental_weekly',   'label' => 'Week'],
                'monthly' => ['field' => 'rental_monthly',  'label' => 'Month'],
            ];

            foreach ($periods as $variant => $cfg) {
                $price = $product->{$cfg['field']} ?? null;
                if (!$price || (float) $price <= 0) {
                    continue;
                }
                $offer = [
                    '@type'           => 'Offer',
                    'name'            => $product->product_name . ' — Rental per ' . $cfg['label'],
                    'price'           => number_format((float) $price, 2, '.', ''),
                    'priceCurrency'   => 'USD',
                    'availability'    => 'https://schema.org/InStock',
                    'url'             => $url . '/' . $variant . '/details',
                    'priceSpecification' => [
                        '@type'       => 'UnitPriceSpecification',
                        'price'       => number_format((float) $price, 2, '.', ''),
                        'priceCurrency' => 'USD',
                        'referenceQuantity' => [
                            '@type'   => 'QuantitativeValue',
                            'value'   => '1',
                            'unitText'=> $cfg['label'],
                        ],
                    ],
                ];
                if ($seller) {
                    $offer['seller'] = $seller;
                }
                $offers[] = $offer;
            }
        }

        if (in_array($product->product_type, ['Retail', 'Both'])) {
            $price     = $product->retail_sale_price ?? $product->retail_price ?? null;
            $basePrice = $product->retail_price ?? null;

            if ($price && (float) $price > 0) {
                $offer = [
                    '@type'         => 'Offer',
                    'price'         => number_format((float) $price, 2, '.', ''),
                    'priceCurrency' => 'USD',
                    'availability'  => 'https://schema.org/InStock',
                    'url'           => $url . '/retail/details',
                ];
                // If sale price is lower than base — mark as reduced
                if ($basePrice && (float) $price < (float) $basePrice) {
                    $offer['priceValidUntil'] = now()->addYear()->format('Y-m-d');
                }
                if ($seller) {
                    $offer['seller'] = $seller;
                }
                $offers[] = $offer;
            }
        }

        return $offers;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Category schema — Service + ItemList
    // ─────────────────────────────────────────────────────────────────────────

    public function buildCategorySchema(ProductCategory $category): array
    {
        $description = $category->seo_description
            ?: strip_tags($category->short_content ?? '')
            ?: strip_tags($category->content ?? '');

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Service',
            'name'        => $category->seo_title ?: $category->title,
            'serviceType' => $category->title,
            'url'         => $this->buildCategoryUrl($category),
            'provider'    => $this->buildOrganizationNode(),
        ];

        if ($description) {
            $schema['description'] = substr(trim($description), 0, 500);
        }

        $imageUrl = $category->media?->getUrl();
        if ($imageUrl) {
            $schema['image'] = $imageUrl;
        }

        return $schema;
    }

    public function buildItemListSchema(ProductCategory $category): array
    {
        $products = $category->publishedProducts ?? collect();
        if ($products->isEmpty()) {
            return [];
        }

        $items = [];
        foreach ($products as $index => $product) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $index + 1,
                'name'     => $product->product_name,
                'url'      => $this->buildProductUrl($product),
            ];
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'name'            => $category->title . ' Equipment',
            'numberOfItems'   => count($items),
            'itemListElement' => $items,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  BreadcrumbList
    // ─────────────────────────────────────────────────────────────────────────

    public function buildBreadcrumbSchema(array $crumbs): array
    {
        $items = [];
        foreach ($crumbs as $index => $crumb) {
            $item = [
                '@type'    => 'ListItem',
                'position' => $index + 1,
                'name'     => $crumb['name'],
            ];
            if (!empty($crumb['url'])) {
                $item['item'] = $crumb['url'];
            }
            $items[] = $item;
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Organization
    // ─────────────────────────────────────────────────────────────────────────

    public function buildOrganizationSchema(): array
    {
        $settings = $this->loadBrandingSettings();

        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => $settings['organization_name'] ?? config('app.name'),
            'url'      => url('/'),
        ];

        if (!empty($settings['site_logo'])) {
            $schema['logo'] = $settings['site_logo'];
        }
        if (!empty($settings['top_phone'])) {
            $schema['telephone'] = $settings['top_phone'];
        }

        // Primary store address
        $primary = Store::active()->where('is_primary', 'Yes')->first();
        if ($primary) {
            $schema['address'] = $this->buildPostalAddress($primary);
            $schema['geo']     = [
                '@type'     => 'GeoCoordinates',
                'latitude'  => $primary->latitude,
                'longitude' => $primary->longitude,
            ];
        }

        return $schema;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  LocalBusiness
    // ─────────────────────────────────────────────────────────────────────────

    public function buildLocalBusinessSchema(Store $store): array
    {
        $settings = $this->loadBrandingSettings();

        $schema = [
            '@context'  => 'https://schema.org',
            '@type'     => 'LocalBusiness',
            'name'      => $store->store_name,
            'address'   => $this->buildPostalAddress($store),
            'telephone' => $store->phone ?? ($settings['top_phone'] ?? null),
            'url'       => url('/'),
        ];

        if ($store->latitude && $store->longitude) {
            $schema['geo'] = [
                '@type'     => 'GeoCoordinates',
                'latitude'  => $store->latitude,
                'longitude' => $store->longitude,
            ];
        }

        if (!empty($store->email)) {
            $schema['email'] = $store->email;
        }

        // Opening hours
        $hours = $this->buildOpeningHours($store);
        if (!empty($hours)) {
            $schema['openingHoursSpecification'] = $hours;
        }

        return $schema;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  FAQPage
    // ─────────────────────────────────────────────────────────────────────────

    public function buildFAQSchema(Collection $faqs): array
    {
        if ($faqs->isEmpty()) {
            return [];
        }

        $entities = $faqs->map(fn($faq) => [
            '@type'          => 'Question',
            'name'           => $faq->question_name,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => strip_tags($faq->answer ?? ''),
            ],
        ])->values()->all();

        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function resolveProductImage(Product $product): ?string
    {
        // Try primary media first
        if ($product->relationLoaded('media') && $product->media) {
            return $product->media->getUrl();
        }

        // Try first child media
        if ($product->relationLoaded('mediaChildren') && $product->mediaChildren->isNotEmpty()) {
            $child = $product->mediaChildren->first();
            if ($child->relationLoaded('media') && $child->media) {
                return $child->media->getUrl();
            }
        }

        return null;
    }

    private function buildProductUrl(Product $product): string
    {
        $variant = $product->product_type === 'Retail' ? 'retail' : 'daily';
        return url('/products/' . $product->slug . '/' . $variant . '/details');
    }

    private function buildCategoryUrl(ProductCategory $category): string
    {
        if ($category->isParentCategory()) {
            return url('/product-categories/' . $category->slug);
        }

        $parent = $category->parentCategory;
        if ($parent) {
            return url('/product-categories/' . $parent->slug . '/' . $category->slug);
        }

        return url('/product-categories/' . $category->slug);
    }

    private function buildPostalAddress(Store $store): array
    {
        return [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $store->address ?? '',
            'addressLocality' => $store->city ?? '',
            'addressRegion'   => $store->state?->code ?? $store->state?->name ?? '',
            'postalCode'      => $store->zip_code ?? '',
            'addressCountry'  => $store->country ?? 'US',
        ];
    }

    private function buildOpeningHours(Store $store): array
    {
        if (!$store->relationLoaded('hoursOfOperation') || $store->hoursOfOperation->isEmpty()) {
            return [];
        }

        return $store->hoursOfOperation->map(fn($h) => [
            '@type'     => 'OpeningHoursSpecification',
            'dayOfWeek' => 'https://schema.org/' . ucfirst(strtolower($h->day_of_week ?? '')),
            'opens'     => $h->open_time ?? '09:00',
            'closes'    => $h->close_time ?? '17:00',
        ])->values()->all();
    }

    private function buildOrganizationNode(): array
    {
        $settings = $this->loadBrandingSettings();

        return [
            '@type' => 'Organization',
            'name'  => $settings['organization_name'] ?? config('app.name'),
            'url'   => url('/'),
        ];
    }

    private function buildSellerNode(): ?array
    {
        $name = $this->resolveBrandName();
        if (!$name) {
            return null;
        }

        return ['@type' => 'Organization', 'name' => $name];
    }

    private function resolveBrandName(): string
    {
        $settings = $this->loadBrandingSettings();
        return $settings['organization_name'] ?? config('app.name', 'Kabba');
    }

    private function loadBrandingSettings(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $cache = Setting::where('setting_type', 'Website Management Branding')
            ->pluck('setting_value', 'setting_name')
            ->toArray();

        return $cache;
    }
}
