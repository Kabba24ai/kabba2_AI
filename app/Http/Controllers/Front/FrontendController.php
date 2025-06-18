<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\ProductManagement\Product;
use Illuminate\Http\Request;
use App\Models\ProductManagement\ProductCategory;


class FrontendController extends Controller
{
    //
    public function index()
    {
        $category_tree = ProductCategory::with('media')->whereNull('parent_id')->limit(8)->get();
        return view('front.index',[
            'metaTitle' => 'Home - Rent n King',
        ])->with('category_tree', $category_tree);
    }

    public function faq()
    {
        return view('front.faq', [
            'metaTitle' => 'Frequently Asked Questions - Rent n King',
        ]);
    }

    public function contact()
    {
        return view('front.contact', [
            'metaTitle' => 'Contact Us - Rent n King',
        ]);
    }


    public function login(){

        return view('front.login', [
            'metaTitle' => 'login - Rent n King',
        ]);

    }

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


        $Products = Product::with('categories','media', 'mediaChildren.media')->whereHas('categories', function ($q) use ($category) {
            $q->where('product_categories.id', $category->id); 
        })->get();

        return view('front.category-listing', [
            'metaTitle' => $category->title . ' - Rent n King',
            'category'=> $category , 
            'Products' => $Products
        ]);

    }

    


    public function product_details($slug){


        $product_details = Product::with('categories', 'mediaChildren.media')->where('slug',$slug)->first();

        return view('front.product_details', [
            'metaTitle' => $product_details->product_name . ' - Rent n King',
            'product_details' => $product_details
        ]);


    }





}
