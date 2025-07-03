<?php

namespace App\Http\Controllers\Front\Checkout;

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
        $order = Order::where('unique_id', $order)->firstOrFail();

        return view('front.checkout.thankyou', [
            'title' => 'Thank-You',
            'order' => $order,
        ]);
    }
}
