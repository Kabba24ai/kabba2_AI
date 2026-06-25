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

            $prefix = $validated['type'];
            $inputFields = array_diff_key($validated, [
                'order_product_unique_id' => null,
                'type'                    => null,
            ]);

            $fields = [];
            foreach ($inputFields as $key => $value) {
                if (!is_null($value)) {
                    $fields["{$prefix}_{$key}"] = $value;
                }
            }

            // Mark delivery/pickup as complete when this endpoint is called,
            // regardless of whether the input fields have values.
            if ($prefix === 'delivery') {
                $fields['is_delivered']        = 1;
                $fields['delivery_is_delivered'] = true;
                $fields['delivery_status']       = 'Completed';
            } else {
                $fields['is_returned']        = 1;
                $fields['pickup_is_delivered']  = true;
                $fields['pickup_status']        = 'Completed';
            }

            $schedule->fill($fields);
            $schedule->save();
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => $validated['type'] === 'delivery'
                    ? 'Failed to update delivery.'
                    : 'Failed to update pickup.',
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'status'  => true,
            'message' => $validated['type'] === 'delivery'
                ? 'Delivery successfully.'
                : 'Pickup successfully.',
        ]);
    }
}
