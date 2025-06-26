<?php

namespace App\Http\Controllers\Front\Products;

use App\Http\Controllers\Controller;

// Models
use App\Models\ProductManagement\Product;
use App\Models\Stores\Store;
use App\Models\Configurations\Setting;


class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke($slug,$productType)
    {
        $productDetail = Product::with('categories','options.items' ,'relatedProducts', 'mediaChildren.media')->where('slug',$slug)->firstOrFail();

        $stores = Store::with('state')->get();

        // $config_setting = Setting::where('setting_type', ['Product Settings'])->get();

        $standard_delivery_range = Setting::where('setting_name', 'standard_delivery_range')->first();
        $extended_delivery_range = Setting::where('setting_name', 'extended_delivery_range')->first();

        
        // dd($productDetail->options->pluck('unique_id')->toArray());

        return view('front.products.details', [
            'title' => $productDetail->product_name,
            'productType'=>$productType,
            'productDetail' => $productDetail ,
            'stores' => $stores ,
            'standard_delivery_range' => $standard_delivery_range ,
            'extended_delivery_range' => $extended_delivery_range ,

        ]);
    }
}
