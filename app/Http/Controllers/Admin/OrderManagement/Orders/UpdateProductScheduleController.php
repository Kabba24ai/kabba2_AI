<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

// Base Controller
use App\Http\Controllers\Controller;

// Events
use App\Events\Admin\Orders\OrderProductScheduleUpdated;

// Models
use App\Models\Orders\OrderProduct;

// Request
use App\Http\Requests\Admin\OrderManagement\Orders\UpdateProductScheduleRequest;

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

        $deliveryChanged = false;
        $pickupChanged = false;
        $storeChange = false;

        // Only update the single field passed for delivery or pickup/return using $validatedData
        if ($validatedData['type'] === 'delivery') {

            $deliveryFields = [
                'delivery_date', 'delivery_time', 'delivery_transport_mode', 'delivery_status', 'delivery_store_id', 'delivery_by'
            ];
            foreach ($deliveryFields as $field) {
                if (array_key_exists($field, $validatedData)) {
                    $orderProduct->$field = $validatedData[$field];
                    // Track if mode was changed
                    if ($field === 'delivery_transport_mode') {
                        $deliveryChanged = true;
                    }
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
                    //
                    if ($field === 'pickup_store_id') {
                        $storeChange = true;
                    }
                    // Track if mode was changed
                    if ($field === 'pickup_transport_mode') {
                        $pickupChanged = true;
                    }
                    break;
                }
            }
        }

        // If either transport mode changed, update service_method and service_option
        if ($deliveryChanged || $pickupChanged) {
            $serviceData = $orderProduct->getServiceMethodFromTransportMode();
            $orderProduct->service_method = $serviceData['service_method'];
            $orderProduct->service_option = $serviceData['service_option'];
        }

        $orderProduct->save();

        if ($storeChange) {
            $equipment = $orderProduct->equipment;
            if ($equipment) {
                $equipment->store_id = $validatedData['pickup_store_id'];
                $equipment->saveQuietly();
            }
        }

        $user = auth()->user();

        // Fire an event for the updated schedule
        $data = [
            'requested_data' => $validatedData,
            'order_product' => $orderProduct->toArray(),
        ];

        event(new OrderProductScheduleUpdated($orderProduct->order, $user, $data));

        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully.'
        ]);
    }
}
