<?php

namespace App\Http\Controllers\Admin\Documents;

use App\Http\Controllers\Controller;
use App\Models\ProductManagement\ProductCategory;
use App\Services\DocumentGenerator\PriceListPresets;

class PriceListFormController extends Controller
{
    /** Category picker for the Dynamic Customer Price List. */
    public function __invoke()
    {
        // Only categories that can contribute rows — published, in website
        // display order, with at least one published product.
        $categories = ProductCategory::query()
            ->published()
            ->sortOrder()
            ->whereHas('products', fn ($q) => $q->published())
            ->get(['id', 'title', 'slug', 'parent_id', 'sort_order']);

        // Industry presets resolved against the selectable categories;
        // presets with no matching categories here are hidden.
        $presets = PriceListPresets::resolve($categories)
            ->filter(fn ($preset) => !empty($preset['category_ids']))
            ->values();

        return view('admin.documents.price_list_form', compact('categories', 'presets'));
    }
}
