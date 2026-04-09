<?php

namespace App\Http\Controllers\Front\Products;

use App\Helpers\CartHelper;
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
            // those products are treated as its parent products.
            $parentProductIds = ProductRelatedProductChild::query()->where('related_product_id', $productDetail->id)->pluck('product_id')->unique()->values();

            $cartData = $request->input('kabba_cart', []);

            if (is_string($cartData)) {
                $decodedCartData = json_decode($cartData, true);
                $cartData = is_array($decodedCartData) ? $decodedCartData : [];
            }

            if (isset($cartData['cart_items']) && is_array($cartData['cart_items'])) {
                $cartData = $cartData['cart_items'];
            }

            if (!is_array($cartData)) {
                $cartData = [];
            }

            $cartItems = [];
            if (!empty($cartData)) {
                // Build summary only when there are cart items.
                $cartSummary = CartHelper::buildCartSummary(['cart_items' => $cartData]);
                $cartItems = $cartSummary['cart_items'] ?? [];
            }

            // Filter matching cart items
            $matchedParentProducts = collect($cartItems)
                ->filter(function ($item) use ($parentProductIds) {
                    return $parentProductIds->contains($item['product_id']);
                })
                ->values();

            // Check if exists
            if ($matchedParentProducts->isNotEmpty()) {
                // You have matched items
                $parentProductDetails = $matchedParentProducts;
            } else {
                $parentProductDetails = collect(); // empty
            }


            $parentRentalLock = null;
            if ($parentProductDetails->isNotEmpty()) {
                $parentItem = $parentProductDetails->first();
                $parentRentalLock = [
                    'enabled' => true,
                    'quantity' => $parentItem['quantity'] ?? null,
                    'delivery_date' => $parentItem['delivery_date'] ?? null,
                    'service_method' => $parentItem['service_method'] ?? null,
                    'distance_type' => $parentItem['distance_type'] ?? null,
                    'service_option' => $parentItem['service_option'] ?? null,
                    'delivery_store_id' => $parentItem['delivery_store_id'] ?? null,
                ];
            }

            return response()->json([
                'success' => true,
                'parent_rental_lock' => $parentRentalLock,
                'html' => view('front.products.details', [
                    'productVariant' => $productVariant,
                    'productDetail' => $productDetail,
                    'stores' => $stores,
                    'productSettings' => $productSettings,
                    'parentProductDetails' => $parentProductDetails
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
