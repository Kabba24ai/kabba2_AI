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

            // Queue Line release enforcement (Phase 4 §2): marking the delivery
            // leg 'Completed' from the admin app is a release — same canonical
            // guard, same structured 422 as the driver-checklist path.
            if ($validated['schedule_type'] === 'Delivery'
                && $validated['schedule_status'] === 'Completed'
                && $schedule->delivery_status !== 'Completed') {
                $schedule->loadMissing('softAssignment.equipment', 'queueLineItem');
                if ($blocked = \App\Services\QueueLine\QueueLineReleaseGuard::check($schedule)) {
                    return response()->json([
                        'status' => false,
                        'success' => false,
                        'message' => $blocked['message'],
                        'error' => $blocked,
                    ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
                }
            }

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
