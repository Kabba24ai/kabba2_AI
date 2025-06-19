<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
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



}
