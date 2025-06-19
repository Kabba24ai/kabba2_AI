<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\ProductManagement\Product;
use Illuminate\Http\Request;
use App\Models\ProductManagement\ProductCategory;

class CategoryController extends Controller
{
    
    public function category_listing($slug){

        $category = ProductCategory::with('media')->whereNull('parent_id')->where('slug',$slug)->first();

        $Products = Product::with('categories','media', 'mediaChildren.media')->whereHas('categories', function ($q) use ($category) {
            $q->where('product_categories.id', $category->id); 
        })->get();

        return view('front.category-listing', [
            'metaTitle' => $category->title . ' - Rent n King',
            'category'=> $category , 
            'Products' => $Products
        ]);

    }


    public function category_child_listing($slug){

        $category = ProductCategory::with('media')->where('slug',$slug)->firstOrFail();


       

        return view('front.category-listing', [
            'metaTitle' => $category->title . ' - Rent n King',
            'category'=> $category , 
            'Products' => $Products
        ]);

    }

    

}
