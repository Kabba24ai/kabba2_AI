<?php

namespace App\Http\Controllers\Front\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Configurations\Setting;

// Models
use App\Models\ProductManagement\ProductCategory;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $category_tree = ProductCategory::has('products')->published()->with('media')->whereNull('parent_id')->sortOrder()->get();

        $order = session('order', []);
        $cartData = $order['cart_data'] ?? [];

        session()->forget('order.cart_data');


          $branding = Setting::where('setting_type', 'Website Management Branding')
            ->pluck('setting_value', 'setting_name')
            ->toArray();

            // dd($branding);
        return view('front.home.index', [
            'title' => 'Equipment Rentals Hickman / Dickson County',
            'category_tree'=> $category_tree,
            'cart_data' => $cartData,
            'branding' => $branding
        ])->with('success', 'Something went wrong. Please try again.');
    }
}
