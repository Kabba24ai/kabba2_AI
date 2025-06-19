<?php

namespace App\Http\Controllers\Front\Products;

use App\Http\Controllers\Controller;

// Models
use App\Models\ProductManagement\Product;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke($slug)
    {
        $product_details = Product::with('categories', 'mediaChildren.media')->where('slug',$slug)->first();

        return view('front.product_details', [
            'title' => $product_details->product_name,
            'product_details' => $product_details ,
        ]);
    }
}
