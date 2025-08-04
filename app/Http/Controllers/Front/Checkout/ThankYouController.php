<?php

namespace App\Http\Controllers\Front\Checkout;

use App\Helpers\CartHelper;
use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use Illuminate\Http\Request;

class ThankYouController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, $order)
    {
        // Find order by unique id (or fail if not found)
        $order = Order::with(['customer', 'billingAddress', 'shippingAddress'])->where('unique_id', $order)->firstOrFail();

        $cartSummary = CartHelper::buildCartSummary([
            'tax_exempt' => $order->is_tax_exempt == "Yes" ? true : false,
            'cart_items' => $order->cart_data
        ]);

        // Render a component (for example, a Blade component)
        $thankYouComponent = view('components.front.checkout.cart-summary', [
            'cart' => $cartSummary,
        ])->render();

        return view('front.checkout.thankyou', [
            'title' => 'Thank-You',
            'order' => $order,
            'thankYouComponent' => $thankYouComponent,
        ]);
    }
}
