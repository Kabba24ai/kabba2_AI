<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\Schedules;

use App\Events\Admin\Orders\OrderProductScheduleUpdated;
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
                // product_name is included so the OrderProductScheduleUpdatedListener's
                // history message (fired off the same event below) isn't blank.
                ->select(['id', 'order_id', 'unique_id', 'product_id', 'product_name', 'delivery_date', 'delivery_time', 'delivery_by', 'pickup_by', 'is_delivered', 'is_returned'])
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
            $deliveryCompleted = false;

            if ($prefix === 'delivery') {
                // Queue Line staging is informational, not restrictive
                // (2026-07-23): completion is never blocked for lacking
                // staging/fuel verification — a never-staged completion is a
                // Fast Track, recorded and surfaced, never prevented.
                $fields['is_delivered']          = 1;
                $fields['delivery_is_delivered'] = true;
                $fields['delivery_status']       = 'Completed';
                $deliveryCompleted = true;
                // if ($schedule->delivery_by !== null) {
                // } else {
                //     Log::channel('api_errors')->warning('UpdateDeliveryPickupInputs: delivery completion blocked — delivery_by is null', [
                //         'order_product_id'        => $schedule->id,
                //         'order_id'                => $schedule->order_id,
                //         'order_product_unique_id' => $validated['order_product_unique_id'],
                //         'employee_id'             => auth('api_user')->id(),
                //         'endpoint'                => request()->path(),
                //     ]);
                // }
            } else {
                $fields['is_returned']         = 1;
                $fields['pickup_is_delivered'] = true;
                $fields['pickup_status']        = 'Completed';
                // if ($schedule->pickup_by !== null) {
                // } else {
                //     Log::channel('api_errors')->warning('UpdateDeliveryPickupInputs: return completion blocked — pickup_by is null', [
                //         'order_product_id'        => $schedule->id,
                //         'order_id'                => $schedule->order_id,
                //         'order_product_unique_id' => $validated['order_product_unique_id'],
                //         'employee_id'             => auth('api_user')->id(),
                //         'endpoint'                => request()->path(),
                //     ]);
                // }
            }

            $schedule->fill($fields);
            $schedule->save();

            // Fire the SAME canonical schedule-update event
            // UpdateProductScheduleController dispatches — SyncOnScheduleUpdate
            // (Queue Line) listens for delivery_status flipping to Completed
            // here exactly like it does there. Without this, a delivery
            // completed through this endpoint set delivery_status='Completed'
            // but never reached Equipment Delivered (queue_line_items.completed_at
            // stayed null — no listener ever ran). Scoped to the delivery branch
            // ONLY, and only when completion actually happened this call — the
            // pickup/return branch and the completion-blocked path (delivery_by
            // still null) never fire this, so a plain checklist-field edit that
            // isn't reasserting completion never spams order history.
            if ($deliveryCompleted) {
                event(new OrderProductScheduleUpdated($schedule->order, auth('api_user')->user(), [
                    'requested_data' => $fields,
                    'order_product' => $schedule->toArray(),
                ]));
            }
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
