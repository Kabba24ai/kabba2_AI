<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

// Base Controller

use App\Enums\Equipments\EquipmentCurrentStatus;
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
        $user = auth()->user();

        $deliveryChanged = false;
        $pickupChanged = false;
        $storeChange = false;
        $deliveryStatusChanged = false;
        $pickupStatusChanged = false;

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

                    if ($field === 'delivery_status') {
                        $deliveryStatusChanged = true;
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

                    if ($field === 'pickup_status') {
                        $pickupStatusChanged = true;
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

        if ($deliveryStatusChanged || $pickupStatusChanged) {
            $equipment = $orderProduct->equipment;
            // If status changed to Completed, set completed_at timestamp
            if ($deliveryStatusChanged) {
                // $orderProduct->delivery_date = now()->format('Y-m-d');
                // $orderProduct->delivery_time = now()->format('H:i');
                if($orderProduct->delivery_status === 'Completed'){
                    $orderProduct->is_delivered = true;
                }else{
                    $orderProduct->is_delivered = false;
                }

                if($equipment){
                    $equipment->current_status = EquipmentCurrentStatus::Rented->value;
                    $equipment->current_status_updated_by = $user->id;
                    $equipment->current_status_changed_at = now();
                    $equipment->saveQuietly();
                }
            }

            if ($pickupStatusChanged) {
                // $orderProduct->pickup_date = now()->format('Y-m-d');
                // $orderProduct->pickup_time = now()->format('H:i');
                if($orderProduct->pickup_status === 'Completed'){
                    $orderProduct->is_returned = true;
                }else{
                    $orderProduct->is_returned = false;
                }

                if($equipment){
                    $equipment->current_status = EquipmentCurrentStatus::Maintenance->value;
                    $equipment->current_status_updated_by = $user->id;
                    $equipment->current_status_changed_at = now();
                    if($orderProduct->pickup_store_id){
                        $equipment->store_id = $orderProduct->pickup_store_id;
                    }
                    $equipment->saveQuietly();
                }
            }

        }

        $orderProduct->save();


        if ($storeChange) {
            $equipment = $orderProduct->equipment;
            if ($equipment) {
                $equipment->store_id = $validatedData['pickup_store_id'];
                $equipment->saveQuietly();
            }
        }

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
