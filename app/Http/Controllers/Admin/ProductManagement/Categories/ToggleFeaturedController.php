<?php

namespace App\Http\Controllers\Admin\ProductManagement\Categories;

use App\Http\Controllers\Controller;

// Models
use App\Models\ProductManagement\ProductCategory;

// Services
use App\Services\Website\HomePageService;

class ToggleFeaturedController extends Controller
{
    /**
     * Toggle whether a category shows in the homepage Featured Rentals grid.
     *
     * @param  string  $unique_id
     * @return \Illuminate\Http\Response
     */
    public function __invoke(string $unique_id)
    {
        $category = ProductCategory::where('unique_id', $unique_id)->firstOrFail();
        $category->update(['is_featured' => $category->is_featured === 'Yes' ? 'No' : 'Yes']);

        HomePageService::clearCache();

        $message = $category->title . ($category->is_featured === 'Yes' ? ' added to' : ' removed from') . ' Featured Rentals.';
        flash($message)->success();

        return back();
    }
}
