<?php

namespace App\Http\Controllers\Front\Products;

use App\Http\Controllers\Controller;

// Models
use App\Models\ProductManagement\Product;
use App\Models\Stores\Store;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke($slug,$productType)
    {
        $productDetail = Product::with('categories','options.items' ,'relatedProducts', 'mediaChildren.media')->where('slug',$slug)->firstOrFail();

        $stores = Store::get();


       

        return view('front.products.details', [
            'title' => $productDetail->product_name,
            'productType'=>$productType,
            'productDetail' => $productDetail ,
            'stores' => $stores ,
        ]);
    }
}
