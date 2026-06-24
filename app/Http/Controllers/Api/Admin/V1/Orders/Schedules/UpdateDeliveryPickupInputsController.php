<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\Schedules;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\V1\Orders\Schedules\UpdateDeliveryPickupInputsRequest;
use App\Models\Orders\OrderProduct;
use Illuminate\Http\JsonResponse;

class UpdateDeliveryPickupInputsController extends Controller
{
    /**
     * Update Delivery & Pickup Input Fields
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(UpdateDeliveryPickupInputsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $schedule = OrderProduct::whereHas('order')
                ->where('unique_id', $validated['order_product_unique_id'])
                ->firstOrFail();

            $fields = array_filter(
                array_diff_key($validated, ['order_product_unique_id' => null]),
                fn($v) => !is_null($v)
            );

            $schedule->fill($fields);
            $schedule->save();
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to update delivery/pickup inputs.',
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Delivery/pickup inputs updated successfully.',
        ]);
    }
}
