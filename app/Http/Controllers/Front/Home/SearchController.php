<?php

namespace App\Http\Controllers\Front\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\ProductManagement\ProductCategory;
use App\Models\ProductManagement\Product;



class SearchController extends Controller
{

    // SearchController.php
    // SearchController.php
    public function __invoke(Request $request)
    {
        $query = $request->get('query');

        // Search products with categories
        $products = Product::with('categories:id,title,slug')
            ->where('product_name', 'LIKE', "%{$query}%")
            ->take(10)
            ->get(['id', 'product_name']);

        // Map each product with its category link(s)
        $products->transform(function ($product) use ($query) {
            $product->category_links = $product->categories->map(function ($cat) use ($query) {
                return route('front.categories.index', ['slug' => $cat->slug]) . '?search=' . urlencode($query);
            });
            return $product;
        });

        // Search categories
        $categories = ProductCategory::where('title', 'LIKE', "%{$query}%")
            ->take(10)
            ->get(['id', 'title', 'slug']);

        return response()->json([
            'products' => $products,
            'categories' => $categories
        ]);
    }
}
