<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\Reorder;

use App\Helpers\SignedUrlHelper;
use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\OrderManagement\Orders\Reorder\PostRequest;

// Models
use App\Models\Orders\Order;


class PostController extends Controller
{
    public function __invoke($orderUniqueId, PostRequest $request)
    {
        $validated = $request->validated();

        try {
            $order = Order::where('unique_id', $orderUniqueId)->firstOrFail();

            $newCartData = [];
            if ($validated['order_type'] === 'existing_order') {
                if (is_array($order->cart_data)) {
                    foreach ($order->cart_data as $cartItem) {
                        $newCartData[] = $cartItem;
                    }
                }
            }

            $signedUrl = SignedUrlHelper::make(
                'front.auth.login.impersonate',
                [
                    'customer_unique_id' => $order->customer->unique_id,
                    'admin_unique_id' => auth()->id(),
                    'order_unique_id' => $order->unique_id,
                    'order_number' => $validated['order_type'] === 'existing_order'
                        ? ($order->reference_order_number ?? $order->order_number)
                        : $order->order_number,
                    'order_type' => $validated['order_type'],
                    'cart_data' => $newCartData,
                ],
                1,
            );
        } catch (\Throwable $th) {
            // Handle the error
            return response()->json([
                'success' => false,
                'error' => 'Failed to reorder the items.'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Redirect to frontend',
            'redirect_url' => $signedUrl,
        ]);

        // $customerCards = $order->customer->cards()->pluck('card_number', 'id')->prepend('Select Card', '');
        // return view('admin.order_management.orders.reorder.index', [
        //     'order' => $order,
        //     'type' => $type,
        //     'customerCards' => $customerCards
        // ]);
    }
}
