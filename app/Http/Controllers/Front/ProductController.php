<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\ProductManagement\Product;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    
    public function product_details($slug){

        $product_details = Product::with('categories', 'mediaChildren.media')->where('slug',$slug)->first();
        
        return view('front.product_details', [
            'metaTitle' => $product_details->product_name . ' - Rent n King',
            'product_details' => $product_details ,
            

        ]);


    }

}
