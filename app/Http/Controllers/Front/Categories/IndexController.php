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
        $category = ProductCategory::published()->with(['media', 'products.media', 'products.mediaChildren.media'])->whereNull('parent_id')->where('slug',$slug)->firstOrFail();

        $products = $category->products;

        return view('front.categories.index', [
            'title' => $category->title,
            'category'=> $category ,
            'products' => $products
        ]);
    }
}
