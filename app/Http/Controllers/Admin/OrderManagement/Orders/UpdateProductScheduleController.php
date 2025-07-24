<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderManagement\Orders\UpdateProductScheduleRequest;
use App\Models\Orders\OrderProduct;

class UpdateProductScheduleController extends Controller
{
    /**
     * Handle updating of a product's delivery/return schedule.
     */
    public function __invoke(UpdateProductScheduleRequest $request, $orderUniqueId, $orderProductUniqueId)
    {
        // Validate order product exists
        $validatedData = $request->validated();

        $orderProduct = OrderProduct::where('unique_id', $orderProductUniqueId)->first();
        if (!$orderProduct) {
            return response()->json(['message' => 'Order product not found.'], 404);
        }

        // Only update the single field passed for delivery or pickup/return using $validatedData
        if ($validatedData['type'] === 'delivery') {
            $deliveryFields = [
                'delivery_date', 'delivery_time', 'delivery_transport_mode', 'delivery_status', 'delivery_store_id', 'delivery_by'
            ];
            foreach ($deliveryFields as $field) {
                if (array_key_exists($field, $validatedData)) {
                    $orderProduct->$field = $validatedData[$field];
                    break;
                }
            }
        }

        if ($validatedData['type'] === 'return') {
            $pickupFields = [
                'pickup_date', 'pickup_time', 'pickup_transport_mode', 'pickup_status', 'pickup_store_id', 'pickup_by'
            ];
            foreach ($pickupFields as $field) {
                if (array_key_exists($field, $validatedData)) {
                    $orderProduct->$field = $validatedData[$field];
                    break;
                }
            }
        }

        $orderProduct->save();

        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully.'
        ]);
    }
}
