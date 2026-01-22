<?php

namespace App\Http\Controllers\Front\Categories;

use App\Http\Controllers\Controller;

// Models
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;

class ChildController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke($slug,$childCategorySlug)
    {
        $category = ProductCategory::published()->with('media')->where('slug',$childCategorySlug)->firstOrFail();

        $products = Product::published()->with('categories','media', 'mediaChildren.media')->whereHas('categories', function ($q) use ($category) {
            $q->where('product_categories.id', $category->id);
        })->get();

        return view('front.categories.index', [
            'title' => $category->title,
            'category'=> $category,
            'products' => $products
        ]);
    }
}
