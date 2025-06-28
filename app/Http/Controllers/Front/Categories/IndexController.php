<?php

namespace App\Http\Controllers\Front\Categories;

use App\Http\Controllers\Controller;

// Models
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke($slug)
    {
        $category = ProductCategory::published()->with('media')->whereNull('parent_id')->where('slug',$slug)->first();

        $products = Product::published()->with('categories','media', 'mediaChildren.media')->whereHas('categories', function ($q) use ($category) {
            $q->where('product_categories.id', $category->id);
        })->get();

        return view('front.categories.index', [
            'title' => $category->title,
            'category'=> $category ,
            'products' => $products
        ]);
    }
}
