<?php

namespace App\Http\Controllers\Front\Cart;

use App\Helpers\CartHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Cart\SaveRequest;
use App\Models\ProductManagement\Product;

class SaveController extends Controller
{
    public function __invoke(SaveRequest $request)
    {
        $validated = $request->validated();
        try {
            $cartItems = CartHelper::buildCartSummary([
                'cart_data' => $validated,
            ]);

            return response()->json([
                'success' => true,
                'cart_items' => $cartItems,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }
}
