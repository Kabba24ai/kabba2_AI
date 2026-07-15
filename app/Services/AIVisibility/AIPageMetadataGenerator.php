<?php

namespace App\Services\AIVisibility;

use App\Models\AIVisibility\AiPageMetadata;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use App\Models\Configurations\Setting;
use Illuminate\Support\Facades\Log;

class AIPageMetadataGenerator
{
    public function __construct(protected SchemaBuilder $schema) {}

    // ─────────────────────────────────────────────────────────────────────────
    //  Public API
    // ─────────────────────────────────────────────────────────────────────────

    public function generateForProduct(Product $product): ?AiPageMetadata
    {
        try {
            $product->loadMissing(['media', 'mediaChildren.media', 'categories', 'relatedProducts']);

            $hash = $this->hashProduct($product);
            $record = AiPageMetadata::findForProduct($product->id);

            if ($record && $record->generated_from_hash === $hash) {
                return $record; // source unchanged — skip
            }

            $schemas   = $this->collectProductSchemas($product);
            $aiTitle   = $this->resolveProductTitle($product);
            $aiSummary = $this->resolveProductSummary($product);

            $data = [
                'page_type'             => 'product',
                'page_id'               => $product->id,
                'url'                   => url('/products/' . $product->slug . '/' . ($product->product_type === 'Retail' ? 'retail' : 'daily') . '/details'),
                'ai_title'              => $aiTitle,
                'ai_summary'            => $aiSummary,
                'ai_keywords'           => $this->buildProductKeywords($product),
                'ai_service_type'       => $product->product_type,
                'ai_use_cases'          => $this->buildProductUseCases($product),
                'ai_area_served'        => $this->resolveAreaServed(),
                'ai_related_categories' => $this->resolveRelatedCategories($product),
                'ai_related_products'   => $this->resolveRelatedProducts($product),
                'schema_json'           => $schemas,
                'generated_from_hash'   => $hash,
                'last_generated_at'     => now(),
            ];

            return AiPageMetadata::updateOrCreate(
                ['page_type' => 'product', 'page_id' => $product->id],
                $data
            );
        } catch (\Throwable $e) {
            Log::error('AIPageMetadataGenerator: product #' . $product->id . ' failed — ' . $e->getMessage());
            return null;
        }
    }

    public function generateForCategory(ProductCategory $category): ?AiPageMetadata
    {
        try {
            $category->loadMissing(['media', 'publishedProducts.media', 'parentCategory']);

            $hash   = $this->hashCategory($category);
            $record = AiPageMetadata::findForCategory($category->id);

            if ($record && $record->generated_from_hash === $hash) {
                return $record;
            }

            $schemas = $this->collectCategorySchemas($category);
            $aiTitle = $category->seo_title ?: $category->title;
            $aiSummary = $category->seo_description
                ?: strip_tags($category->short_content ?? '')
                ?: strip_tags($category->content ?? '');

            $data = [
                'page_type'             => 'category',
                'page_id'               => $category->id,
                'url'                   => url('/product-categories/' . $category->slug),
                'ai_title'              => substr($aiTitle, 0, 255),
                'ai_summary'            => substr(trim($aiSummary), 0, 500),
                'ai_keywords'           => $this->buildCategoryKeywords($category),
                'ai_service_type'       => 'Rental',
                'ai_use_cases'          => [],
                'ai_area_served'        => $this->resolveAreaServed(),
                'ai_related_categories' => $this->resolveChildCategoryNames($category),
                'ai_related_products'   => $this->resolvePublishedProductNames($category),
                'schema_json'           => $schemas,
                'generated_from_hash'   => $hash,
                'last_generated_at'     => now(),
            ];

            return AiPageMetadata::updateOrCreate(
                ['page_type' => 'category', 'page_id' => $category->id],
                $data
            );
        } catch (\Throwable $e) {
            Log::error('AIPageMetadataGenerator: category #' . $category->id . ' failed — ' . $e->getMessage());
            return null;
        }
    }

    public function generateForHome(): ?AiPageMetadata
    {
        try {
            // Canonical homepage SEO lives on the home WebsitePage
            // (Home Page Builder → SEO); the retired Branding
            // home_seo_* settings survive only as a read fallback.
            $homePage = \App\Models\WebsiteManagement\WebsitePage::where('page_key', 'home')
                ->first(['id', 'meta_title', 'meta_description']);

            $settings = Setting::where('setting_type', 'Website Management Branding')
                ->whereIn('setting_name', ['home_seo_title', 'home_seo_description'])
                ->pluck('setting_value', 'setting_name');

            $aiTitle   = $homePage?->meta_title
                ?: ($settings['home_seo_title'] ?? null)
                ?: config('app.name');
            $aiSummary = $homePage?->meta_description
                ?: ($settings['home_seo_description'] ?? null);

            $hash   = md5(config('app.name') . $aiTitle . $aiSummary . now()->format('Y-m-d'));
            $record = AiPageMetadata::findForHome();

            if ($record && $record->generated_from_hash === $hash) {
                return $record;
            }

            $org     = $this->schema->buildOrganizationSchema();
            $schemas = [$org];

            $primary = Store::active()->where('is_primary', 'Yes')->with('hoursOfOperation')->first();
            if ($primary) {
                $schemas[] = $this->schema->buildLocalBusinessSchema($primary);
            }

            return AiPageMetadata::updateOrCreate(
                ['page_type' => 'home', 'page_id' => null],
                [
                    'page_type'           => 'home',
                    'page_id'             => null,
                    'url'                 => url('/'),
                    'ai_title'            => $aiTitle,
                    'ai_summary'          => $aiSummary,
                    'ai_keywords'         => [],
                    'schema_json'         => $schemas,
                    'generated_from_hash' => $hash,
                    'last_generated_at'   => now(),
                ]
            );
        } catch (\Throwable $e) {
            Log::error('AIPageMetadataGenerator: home failed — ' . $e->getMessage());
            return null;
        }
    }

    public function deleteForProduct(int $productId): void
    {
        AiPageMetadata::where('page_type', 'product')->where('page_id', $productId)->delete();
    }

    public function deleteForCategory(int $categoryId): void
    {
        AiPageMetadata::where('page_type', 'category')->where('page_id', $categoryId)->delete();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Schema collectors
    // ─────────────────────────────────────────────────────────────────────────

    private function collectProductSchemas(Product $product): array
    {
        $schemas = [];

        $schemas[] = $this->schema->buildProductSchema($product);

        // Breadcrumb
        $crumbs = [['name' => 'Home', 'url' => url('/')]];
        $category = $product->categories->first();
        if ($category) {
            $parent = $category->isParentCategory() ? $category : $category->parentCategory;
            if ($parent) {
                $crumbs[] = ['name' => $parent->title, 'url' => url('/product-categories/' . $parent->slug)];
            }
            if (!$category->isParentCategory()) {
                $crumbs[] = ['name' => $category->title, 'url' => url('/product-categories/' . ($parent?->slug ?? '') . '/' . $category->slug)];
            }
        }
        $crumbs[] = ['name' => $product->product_name, 'url' => null];
        $schemas[] = $this->schema->buildBreadcrumbSchema($crumbs);

        // Organization
        $schemas[] = $this->schema->buildOrganizationSchema();

        return array_values(array_filter($schemas));
    }

    private function collectCategorySchemas(ProductCategory $category): array
    {
        $schemas = [];

        $schemas[] = $this->schema->buildCategorySchema($category);

        $itemList = $this->schema->buildItemListSchema($category);
        if (!empty($itemList)) {
            $schemas[] = $itemList;
        }

        // Breadcrumb
        $crumbs = [['name' => 'Home', 'url' => url('/')]];
        if (!$category->isParentCategory() && $category->parentCategory) {
            $parent = $category->parentCategory;
            $crumbs[] = ['name' => $parent->title, 'url' => url('/product-categories/' . $parent->slug)];
        }
        $crumbs[] = ['name' => $category->title, 'url' => null];
        $schemas[] = $this->schema->buildBreadcrumbSchema($crumbs);

        $schemas[] = $this->schema->buildOrganizationSchema();

        return array_values(array_filter($schemas));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Field resolvers
    // ─────────────────────────────────────────────────────────────────────────

    private function resolveProductTitle(Product $product): string
    {
        return substr($product->seo_title ?: $product->product_name, 0, 255);
    }

    private function resolveProductSummary(Product $product): string
    {
        $text = $product->seo_description
            ?: strip_tags($product->short_description ?? '')
            ?: strip_tags($product->description ?? '');
        return substr(trim($text), 0, 500);
    }

    private function buildProductKeywords(Product $product): array
    {
        $keywords = [$product->product_name];

        if ($product->product_type === 'Rental' || $product->product_type === 'Both') {
            $keywords[] = $product->product_name . ' rental';
            $keywords[] = $product->product_name . ' for rent';
        }
        if ($product->product_type === 'Retail' || $product->product_type === 'Both') {
            $keywords[] = $product->product_name . ' for sale';
            $keywords[] = 'buy ' . $product->product_name;
        }

        foreach ($product->categories as $cat) {
            $keywords[] = $cat->title;
        }

        if (!empty($product->sku)) {
            $keywords[] = $product->sku;
        }

        return array_values(array_unique($keywords));
    }

    private function buildProductUseCases(Product $product): array
    {
        $uses = [];

        if ($product->delivery_and_pickup) {
            $uses[] = 'delivery and pickup available';
        }
        if ($product->in_store_pickup) {
            $uses[] = 'in-store pickup available';
        }

        foreach ($product->categories as $cat) {
            $uses[] = $cat->title . ' applications';
        }

        return array_values(array_unique($uses));
    }

    private function buildCategoryKeywords(ProductCategory $category): array
    {
        $keywords = [$category->title, $category->title . ' rental', $category->title . ' equipment'];

        if ($category->publishedProducts) {
            foreach ($category->publishedProducts->take(10) as $product) {
                $keywords[] = $product->product_name;
            }
        }

        return array_values(array_unique($keywords));
    }

    private function resolveAreaServed(): array
    {
        // Use the SchemaBuilder's combined area-served list (service area rows take
        // precedence; falls back to store city/state/zip when no areas are defined)
        $nodes = $this->schema->buildAreaServed();

        if (!empty($nodes)) {
            return $nodes;
        }

        // Absolute fallback: all active store addresses
        return Store::active()->get()->map(fn($s) => array_filter([
            'city'    => $s->city,
            'state'   => $s->state?->name,
            'zipCode' => $s->zip_code,
        ]))->values()->all();
    }

    private function resolveRelatedCategories(Product $product): array
    {
        return $product->categories->map(fn($c) => [
            'id'    => $c->id,
            'title' => $c->title,
            'slug'  => $c->slug,
        ])->values()->all();
    }

    private function resolveRelatedProducts(Product $product): array
    {
        if (!$product->relationLoaded('relatedProducts')) {
            return [];
        }

        return $product->relatedProducts->map(fn($p) => [
            'id'   => $p->id,
            'name' => $p->product_name,
            'slug' => $p->slug,
        ])->values()->all();
    }

    private function resolveChildCategoryNames(ProductCategory $category): array
    {
        return $category->childCategories()
            ->published()
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'title' => $c->title, 'slug' => $c->slug])
            ->values()
            ->all();
    }

    private function resolvePublishedProductNames(ProductCategory $category): array
    {
        if (!$category->publishedProducts) {
            return [];
        }

        return $category->publishedProducts->map(fn($p) => [
            'id'   => $p->id,
            'name' => $p->product_name,
            'slug' => $p->slug,
        ])->values()->all();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Hash helpers — detect source data changes
    // ─────────────────────────────────────────────────────────────────────────

    private function hashProduct(Product $product): string
    {
        $data = [
            $product->product_name,
            $product->slug,
            $product->product_type,
            $product->seo_title,
            $product->seo_description,
            $product->short_description,
            $product->retail_price,
            $product->retail_sale_price,
            $product->rental_daily,
            $product->rental_weekend,
            $product->rental_weekly,
            $product->rental_monthly,
            $product->in_store_pickup,
            $product->delivery_and_pickup,
            $product->media_id,
            $product->updated_at?->toIso8601String(),
        ];

        return md5(implode('|', $data));
    }

    private function hashCategory(ProductCategory $category): string
    {
        $data = [
            $category->title,
            $category->slug,
            $category->seo_title,
            $category->seo_description,
            $category->short_content,
            $category->media_id,
            $category->parent_id,
            $category->updated_at?->toIso8601String(),
        ];

        return md5(implode('|', $data));
    }
}
