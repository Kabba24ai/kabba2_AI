<?php

namespace App\Http\Controllers\Admin\ProductManagement\Products;

use App\Http\Controllers\Controller;

// Models
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductRelatedProductChild;

class ProductSearchController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke($search, $currentProductUniqueId = null)
    {
        $search = trim($search);

        // Get the current product by unique_id if provided
        $currentProduct = null;
        $parentProductIds = [];

        if ($currentProductUniqueId) {
            $currentProduct = Product::where('unique_id', $currentProductUniqueId)->first();

            // Find all parent products of the current product
            // (products that have the current product as a related_product_id)
            if ($currentProduct) {
                $parentProductIds = ProductRelatedProductChild::where('related_product_id', $currentProduct->id)
                    ->pluck('product_id')
                    ->toArray();
            }
        }

        // Get all products matching search (don't exclude parents)
        $products = Product::with('options')
            ->when($currentProductUniqueId, fn($qb) => $qb
                ->where('unique_id', '!=', $currentProductUniqueId)
            )
            ->when($search, fn($qb) => $qb->where('product_name', 'like', "%{$search}%"))
            ->get()
            ->map(function($product) use ($parentProductIds) {
                // Mark if this product is a parent (disabled/non-selectable)
                $isParent = in_array($product->id, $parentProductIds);
                return [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'image_url' => $product->image_url,
                    'is_parent' => $isParent,
                ];
            });

        // Determine if warning should be shown
        $showParentWarning = !empty($parentProductIds);

        return response()->json([
            'success' => true,
            'products' => $products,
            'parent_warning' => $showParentWarning ? 'Some products are excluded because they are already parents of this product.' : null,
        ]);
    }
}
