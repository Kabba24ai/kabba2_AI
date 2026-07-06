<?php

namespace App\Http\Controllers\Admin\Documents\Presets;

use App\Http\Controllers\Controller;
use App\Models\Documents\PriceListPreset;
use App\Models\ProductManagement\ProductCategory;

class EditController extends Controller
{
    public function __invoke(PriceListPreset $preset)
    {
        // Alphabetical for easy finding — admin selection UI only.
        $categories = ProductCategory::query()
            ->published()
            ->orderBy('title')
            ->get(['id', 'title']);

        $selectedIds = $preset->categories()->pluck('product_categories.id')->all();

        return view('admin.documents.presets.edit', compact('preset', 'categories', 'selectedIds'));
    }
}
