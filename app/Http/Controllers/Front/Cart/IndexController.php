<?php

namespace App\Http\Controllers\Front\Cart;

use App\Helpers\CartHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        // 1. Get cart from session or backend storage (not localStorage)
        $cartData = $request->kabba_cart ?? [];

        // 2. Build summary using CartHelper
        $cartSummary = CartHelper::buildCartSummary(['cart_items' => $cartData]);

        // 3. Pass to component/view
        return response()->json([
            'sidebar' => view('components.front.cart-sidebar-preview', [
                'cart' => $cartSummary,
            ])->render(),
            'summary' => view('components.front.checkout.cart-summary', [
                'cart' => $cartSummary,
            ])->render(),
            'hide_cc_payment_option' => $cartSummary['hide_cc_payment_option'] ?? false,
        ]);
    }
}
