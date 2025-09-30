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
        $category = ProductCategory::published()->with('media')
            ->whereNull('parent_id')->where('slug', $slug)->firstOrFail();

        $products = Product::published()->with('categories', 'media', 'mediaChildren.media')
            ->whereHas('categories', function ($q) use ($category) {
                $q->where('product_categories.id', $category->id);
            });

        // Apply keyword filter if present
        if ($request->has('search') && $request->search != '') {
            $keyword = $request->search;
            $products = $products->where('product_name', 'LIKE', "%{$keyword}%");
        }

        $products = $products->get();

        return view('front.categories.index', [
            'title' => $category->title,
            'category' => $category,
            'products' => $products
        ]);
    }


    // public function __invoke($slug)
    // {
    //     $category = ProductCategory::published()->with('media')->whereNull('parent_id')->where('slug',$slug)->firstOrFail();

    //     $products = Product::published()->with('categories','media', 'mediaChildren.media')->whereHas('categories', function ($q) use ($category) {
    //         $q->where('product_categories.id', $category->id);
    //     })->get();

    //     return view('front.categories.index', [
    //         'title' => $category->title,
    //         'category'=> $category ,
    //         'products' => $products
    //     ]);
    // }
}
