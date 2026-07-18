<?php

namespace App\Services\Website;

use App\Models\WebsiteManagement\WebsitePage;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;

class WebsitePageBuilderService
{
    /**
     * Load all builder context for a page.
     * Returns the same keys the Home Builder controller compiles,
     * making it safe to pass directly to ComponentRegistry::viewData().
     *
     * Extra data (categories, stores) is only queried when the page
     * actually has the relevant sections, avoiding unnecessary DB hits
     * on pages that don't use those components.
     */
    public function getBuilderContext(WebsitePage $page): array
    {
        $page->load(['sections.items']);

        // Key by section_type (component key) so registry lookups via $this->key() work.
        // Falls back to section_key for legacy rows where section_type is null.
        $typeKey            = fn ($s) => $s->section_type ?? $s->section_key;
        $sections           = $page->sections->keyBy($typeKey);
        $itemsByKey         = $page->sections->mapWithKeys(fn ($s) => [$typeKey($s) => $s->items]);
        $allSectionsOrdered = $page->sections->sortBy('display_order')->values();

        $categories = $sections->has('featured_rentals')
            ? ProductCategory::has('products')
                ->published()
                ->whereNull('parent_id')
                ->sortOrder()
                ->get(['id', 'unique_id', 'title', 'slug'])
            : collect();

        $selectedCategoryIds = $sections->has('featured_rentals')
            ? $itemsByKey->get('featured_rentals', collect())
                ->map(fn ($i) => data_get($i->content, 'category_id'))
                ->filter()
            : collect();

        $needsStores = $sections->has('contact_strip') || $sections->has('locations');
        $stores = $needsStores
            ? Store::with(['hoursOfOperation', 'state'])
                ->active()
                ->orderByAdmin()
                ->get(['id', 'unique_id', 'slug', 'store_name', 'address', 'city', 'zip_code', 'phone', 'details', 'latitude', 'longitude', 'state_id'])
            : collect();

        return compact(
            'page',
            'sections',
            'itemsByKey',
            'allSectionsOrdered',
            'categories',
            'selectedCategoryIds',
            'stores'
        );
    }
}
