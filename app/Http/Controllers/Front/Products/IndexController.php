<?php

namespace App\Http\Controllers\Front\Products;

use App\Http\Controllers\Controller;

// Helpers
use App\Helpers\ConfigurationHelper;

// Models
use App\Models\ProductManagement\Product;
use App\Models\Stores\Store;
use App\Models\Configurations\Setting;
use App\Models\ProductManagement\ProductCategory;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke($categorySlug, $slug, $productType)
    {
        $productDetail = Product::published()->with('categories', 'options.items', 'relatedProducts', 'mediaChildren.media')->where('slug', $slug)->firstOrFail();

        $stores = Store::with('state')->get();

        // 1. Find the category from the product's categories by slug
        $category = $productDetail->categories->where('slug', $categorySlug)->first();

        if (!$category) {
            $parentCategory = null;
            $childCategories = collect();
        } else {
            // 2. If it's a parent, get its children that are ALSO linked to this product and are published
            if (is_null($category->parent_id)) {
                $parentCategory = $category;
                // Find children of this parent that are linked to the product and published
                $childCategories = $productDetail->categories
                    ->where('parent_id', $category->id)
                    ->where('is_published', true)
                    ->values();
            } else {
                // If it's a child, find its parent (must also be linked and published)
                $parentCategory = $category->parentCategory;
                $childCategories = $parentCategory ? collect([$category]) : collect();
            }
        }

        $productSettings = ConfigurationHelper::getSettings('Product Settings');

        $standard_delivery_range = $productSettings['standard_delivery_range'];
        $extended_delivery_range = $productSettings['extended_delivery_range'];
        $taxValue = $productSettings['sales_tax'];

        return view('front.products.details', [
            'title' => $productDetail->product_name,
            'category' => $category,
            'productType' => $productType,
            'productDetail' => $productDetail,
            'stores' => $stores,
            'standard_delivery_range' => $standard_delivery_range,
            'extended_delivery_range' => $extended_delivery_range,
            'taxRate' => $taxValue,
            'parentCategory' => $parentCategory,
            'childCategories' => $childCategories,
        ]);
    }
}
