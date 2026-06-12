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

        $scheduleType = $validated['schedule_type'];

        $orderProduct = OrderProduct::whereHas('order')
            ->where('unique_id', $validated['order_product_unique_id'])
            ->first();

        $user = User::where('unique_id', $validated['user_unique_id'])->first();


        if (!$orderProduct || !$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Order Product or User selected.'
            ], 404);
        }

        if ($scheduleType === 'Delivery') {
            $orderProduct->delivery_by = $user->id;
        } else if ($scheduleType === 'Pickup') {
            $orderProduct->pickup_by = $user->id;
        }

        $orderProduct->save();

        return response()->json([
            'success' => true,
            'message' => 'Driver assigned successfully!',
        ]);
    }
}
