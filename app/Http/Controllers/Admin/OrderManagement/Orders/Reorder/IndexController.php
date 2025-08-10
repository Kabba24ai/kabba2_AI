<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\Reorder;

use App\Helpers\SignedUrlHelper;
use App\Http\Controllers\Controller;
use App\Models\Orders\Order;

class IndexController extends Controller
{
    public function __invoke($orderUniqueId, $type)
    {
        $order = Order::where('unique_id', $orderUniqueId)->firstOrFail();

        // Set 'delivery_date' to null in each cart_data array, then store in new variable
        // $newCartData = [];
        // if (is_array($order->cart_data)) {
        //     foreach ($order->cart_data as $cartItem) {
        //         if (isset($cartItem['delivery_date'])) {
        //             $cartItem['delivery_date'] = ;
        //         }
        //         $newCartData[] = $cartItem;
        //     }
        // }

        $signedUrl = SignedUrlHelper::make(
            'front.auth.login.impersonate',
            [
                'customer_unique_id' => $order->customer->unique_id,
                'admin_unique_id' => auth()->id(),
                'order_unique_id' => $order->unique_id,
                'order_number' => $order->order_number,
                'order_type' => $type,
                'cart_data' => $order->cart_data, // Pass the cart data if needed
            ],
            1,
        );
        return redirect($signedUrl);

        // $customerCards = $order->customer->cards()->pluck('card_number', 'id')->prepend('Select Card', '');
        // return view('admin.order_management.orders.reorder.index', [
        //     'order' => $order,
        //     'type' => $type,
        //     'customerCards' => $customerCards
        // ]);
    }
}
