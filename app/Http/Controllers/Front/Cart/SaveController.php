<?php

namespace App\Http\Controllers\Front\Cart;

use App\Helpers\CartHelper;
use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Front\Cart\SaveRequest;

class SaveController extends Controller
{
    public function __invoke(SaveRequest $request)
    {
        $validated = $request->validated();
        try {
            $cartData = CartHelper::buildCartSummary([
                'cart_items' => $validated,
            ]);

            return response()->json([
                'success' => true,
                'cart_data' => $cartData,
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
