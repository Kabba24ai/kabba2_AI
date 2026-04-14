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
            ->with([
                'media',
                'categoryChildren.product.media',
                'categoryChildren.subCategory.media',
                'categoryChildren.subCategory.parentCategory', // subcategory's parent
                'publishedProducts.media',
                'publishedProducts.mediaChildren.media',
            ])
            ->whereNull('parent_id')
            ->where('slug', $slug)
            ->firstOrFail();

        // categoryChildren contains both products and subcategories with sort_order
        $items = $category->categoryChildren->map(function ($child) {
            if ($child->product && $child->product->status === 'Published') {
                $child->product->item_type = 'product';
                $child->product->sort_order = $child->sort_order;
                return $child->product;
            } elseif ($child->subCategory && $child->subCategory->status === 'Published') {
                $child->subCategory->item_type = 'subcategory';
                $child->subCategory->sort_order = $child->sort_order;
                return $child->subCategory;
            }
            return null;
        })->filter()->sortBy('sort_order')->values();

        // foreach ($items as $item) {
        //     if($item->item_type === 'subcategory') {
        //         $parentCategorySlug = $item->parentCategory->slug ?? null;

        //         $categoryRoute = $parentCategorySlug
        //         ? route('front.categories.sub-category', ['slug' => $parentCategorySlug, 'childCategorySlug' => $item->slug])
        //         : route('front.categories.index', ['slug' => $item->slug]);
        //         dump($parentCategorySlug, $categoryRoute);
        //     }
        // }

        // dd("here");

        return view('front.categories.index', [
            'title' => $category->title,
            'category' => $category,
            'items' => $items,
        ]);
    }
}
