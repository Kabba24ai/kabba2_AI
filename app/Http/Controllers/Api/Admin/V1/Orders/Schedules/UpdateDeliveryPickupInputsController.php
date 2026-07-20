<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\Schedules;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\V1\Orders\Schedules\UpdateDeliveryPickupInputsRequest;
use App\Models\Orders\OrderProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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
                // product_id + delivery_date/time are required by the model's
                // updated hook (FunnelLifecycleService) when this request is
                // the one that flips delivery_status to Completed — without
                // them the partial select made that edge throw a TypeError.
                ->select(['id', 'order_id', 'unique_id', 'product_id', 'delivery_date', 'delivery_time', 'delivery_by', 'pickup_by', 'is_delivered', 'is_returned'])
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

            // Only mark delivery/return complete if the primary workflow controller has
            // already run. SaveDeliveryController sets delivery_by; SaveReturnController
            // sets pickup_by. A null FK means the real workflow hasn't happened yet —
            // updating T&C/license/video/checklist status strings is still allowed, but
            // claiming completion is not.
            if ($prefix === 'delivery') {
                if ($schedule->delivery_by !== null) {
                    // Queue Line release enforcement (Phase 4 §2): this branch
                    // marks the delivery Completed, so it obeys the canonical
                    // guard. Items already released through a guarded path are
                    // completed (latched) and pass through untouched.
                    $guardTarget = OrderProduct::with('softAssignment.equipment', 'queueLineItem')
                        ->find($schedule->id);
                    if ($guardTarget && ($blocked = \App\Services\QueueLine\QueueLineReleaseGuard::check($guardTarget))) {
                        return response()->json([
                            'status' => false,
                            'success' => false,
                            'message' => $blocked['message'],
                            'error' => $blocked,
                        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    $fields['is_delivered']          = 1;
                    $fields['delivery_is_delivered'] = true;
                    $fields['delivery_status']       = 'Completed';
                } else {
                    Log::channel('api_errors')->warning('UpdateDeliveryPickupInputs: delivery completion blocked — delivery_by is null', [
                        'order_product_id'        => $schedule->id,
                        'order_id'                => $schedule->order_id,
                        'order_product_unique_id' => $validated['order_product_unique_id'],
                        'employee_id'             => auth('api_user')->id(),
                        'endpoint'                => request()->path(),
                    ]);
                }
            } else {
                if ($schedule->pickup_by !== null) {
                    $fields['is_returned']         = 1;
                    $fields['pickup_is_delivered'] = true;
                    $fields['pickup_status']        = 'Completed';
                } else {
                    Log::channel('api_errors')->warning('UpdateDeliveryPickupInputs: return completion blocked — pickup_by is null', [
                        'order_product_id'        => $schedule->id,
                        'order_id'                => $schedule->order_id,
                        'order_product_unique_id' => $validated['order_product_unique_id'],
                        'employee_id'             => auth('api_user')->id(),
                        'endpoint'                => request()->path(),
                    ]);
                }
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
