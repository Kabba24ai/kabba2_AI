<?php

namespace App\Http\Controllers\Front\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\ProductManagement\ProductCategory;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $category_tree = ProductCategory::published()->with('media')->whereNull('parent_id')->sortOrder()->get();

        $order = session('order', []);
        $cartData = $order['cart_data'] ?? [];

        session()->forget('order.cart_data');

        return view('front.home.index', [
            'title' => 'Equipment Rentals Hickman / Dickson County',
            'category_tree'=> $category_tree,
            'cart_data' => $cartData,
        ])->with('success', 'Something went wrong. Please try again.');
    }
}
