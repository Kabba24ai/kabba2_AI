<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\Schedules;

use App\Http\Controllers\Controller;

// Events
use App\Events\Admin\Orders\OrderProductScheduleUpdated;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\Schedules\UpdateRequest;

// Models
use App\Models\Orders\OrderProduct;
use Illuminate\Http\JsonResponse;

class UpdateController extends Controller
{
    /**
     * Orders Schedule Update
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(UpdateRequest $request)
    {
        $validated = $request->validated();
        try {
            $user = auth('api_user')->user();
            $schedule = OrderProduct::with('order')
                ->whereHas('order')
                ->where('unique_id', $validated['order_product_unique_id'])
                ->firstOrFail();

            // Queue Line staging is informational, not restrictive (2026-07-23):
            // marking the delivery leg 'Completed' from the admin app is never
            // blocked for lacking staging/fuel verification — a never-staged
            // completion is a Fast Track, recorded and surfaced, never
            // prevented. SyncOnScheduleUpdate records completion off the event
            // dispatched below.

            if ($validated['schedule_type'] === 'Delivery') {
                $field = 'delivery_status';
                $schedule->delivery_status = $validated['schedule_status'];
                $schedule->delivery_by = $user->id;

            }else{
                $field = 'pickup_status';
                $schedule->pickup_status = $validated['schedule_status'];
                $schedule->pickup_by = $user->id;
            }

            $schedule->save();

            $user = auth('api_user')->user();

            // Fire an event for the updated schedule
            $data = [
                'requested_data' => [
                    'type' => strtolower($validated['schedule_type']),
                    $field => $validated['schedule_status'],
                ],
                'order_product' => $schedule->toArray(),
            ];

            event(new OrderProductScheduleUpdated($schedule->order, $user, $data));
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => false,
                'message' => trans('messages.api.admin.v1.orders.schedule_update_failed'),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'status' => true,
            'message' => trans('messages.api.admin.v1.orders.schedule_updated'),
        ]);
    }
}
