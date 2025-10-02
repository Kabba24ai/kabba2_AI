<?php

namespace App\Http\Controllers\Front\Categories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */

    public function __invoke($slug, Request $request)
    {
        $category = ProductCategory::published()
            ->with(['media', 'publishedProducts.media', 'publishedProducts.mediaChildren.media'])
            ->whereNull('parent_id')
            ->where('slug', $slug)
            ->firstOrFail();

        $products = $category->publishedProducts;

        if ($request->has('search') && $request->search != '' && count($products) > 0) {
            $keyword = $request->search;
            $products = $products->filter(function ($product) use ($keyword) {
                return stripos($product->product_name, $keyword) !== false;
            });
        }

        return view('front.categories.index', [
            'title' => $category->title,
            'category' => $category,
            'products' => $products,
        ]);
    }
}
