<?php

namespace App\Http\Controllers\Front\Products;

use App\Http\Controllers\Controller;

// Helpers
use App\Helpers\ConfigurationHelper;

// Models
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductRelatedProductChild;
use App\Models\Stores\Store;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke($slug, $productVariant, Request $request)
    {
        $productDetail = Product::published()->with('categories', 'options.items', 'relatedProducts', 'mediaChildren.media')->where('slug', $slug)->firstOrFail();

        // If current product is used as a related item in any other product,
        // those products are treated as its parent products.
        $parentProductIds = ProductRelatedProductChild::query()
            ->where('related_product_id', $productDetail->id)
            ->pluck('product_id')
            ->unique()
            ->values();

        $stores = Store::active()->with('state')->get();

        // 1. Find the category from the product's categories by slug
        $category = $productDetail->categories->sortBy('slug')->first();

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
                    ->filter(function ($cat) {
                        return $cat->published();
                    })
                    ->values();
            } else {
                // If it's a child, find its parent (must also be linked and published)
                $parentCategory = $category->parentCategory;
                $childCategories = $parentCategory ? collect([$category]) : collect();
            }
        }

        $productSettings = ConfigurationHelper::getSettings('Product Settings');


        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('front.products.details', [
                    'productVariant' => $productVariant,
                    'productDetail' => $productDetail,
                    'stores' => $stores,
                    'productSettings' => $productSettings,
                ])->render(),
            ]);
        }

        return view('front.products.index', [
            'title' => $productDetail->product_name,
            'category' => $category,
            'productVariant' => $productVariant,
            'productDetail' => $productDetail,
            'stores' => $stores,
            'parentCategory' => $parentCategory,
            'childCategories' => $childCategories,
            'productSettings' => $productSettings,
        ]);
    }
}
