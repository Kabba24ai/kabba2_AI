<?php

namespace App\Http\Controllers\Admin\Documents;

use App\Http\Controllers\Controller;
use App\Models\Documents\PriceListPreset;
use App\Models\ProductManagement\ProductCategory;

class PriceListFormController extends Controller
{
    /** Category picker for the Dynamic Customer Price List. */
    public function __invoke()
    {
        // Only categories that can contribute rows — published, with at
        // least one published product. Alphabetical for easy finding: this
        // is the admin selection UI only — the generated document re-sorts
        // into the customer-facing website display order.
        $categories = ProductCategory::query()
            ->published()
            ->orderBy('title')
            ->whereHas('products', fn ($q) => $q->published())
            ->get(['id', 'title', 'slug', 'parent_id', 'sort_order']);

        $selectableIds = $categories->pluck('id')->all();

        // Active admin-managed presets, restricted to categories that are
        // actually selectable here; presets left with none are hidden.
        // Presets only pre-check checkboxes — generation stays purely
        // category-id based.
        $presets = PriceListPreset::query()
            ->active()
            ->ordered()
            ->with(['categories:product_categories.id', 'thumbnail'])
            ->get()
            ->map(fn (PriceListPreset $preset) => [
                'key'           => $preset->id,
                'label'         => $preset->name,
                'description'   => (string) $preset->description,
                'thumbnail_url' => $preset->thumbnail_url,
                'category_ids'  => $preset->categories->pluck('id')
                    ->intersect($selectableIds)->values()->all(),
            ])
            ->filter(fn (array $preset) => !empty($preset['category_ids']))
            ->values();

        return view('admin.documents.price_list_form', compact('categories', 'presets'));
    }
}
