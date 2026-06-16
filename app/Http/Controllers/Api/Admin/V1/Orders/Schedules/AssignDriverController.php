<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\Schedules;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\Schedules\AssignDriverRequest;

// Models
use App\Models\Orders\OrderProduct;
use App\Models\Iam\Personnel\User;

class AssignDriverController extends Controller
{
    /**
     * Assign Driver to Order Product
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(AssignDriverRequest $request)
    {
        $validated = $request->validated();


        $orderProduct = OrderProduct::whereHas('order')
            ->where('unique_id', $validated['order_product_unique_id'])
            ->first();

        if (!$orderProduct) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Order Product selected.'
            ], 404);
        }

        if (!empty($validated['user_delivery_id'])) {
            $orderProduct->delivery_by = $validated['user_delivery_id'];
        }
        if (!empty($validated['user_pickup_id'])) {
            $orderProduct->pickup_by = $validated['user_pickup_id'];
        }

        $orderProduct->save();

        return response()->json([
            'success' => true,
            'message' => 'Driver assigned successfully!',
        ]);
    }
}
