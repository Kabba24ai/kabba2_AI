<?php

namespace App\Http\Controllers\Api\Admin\V1\Dispatch;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\V1\Dispatch\UpdateStatusRequest;
use App\Events\Admin\Orders\OrderProductScheduleUpdated;
use App\Models\Orders\OrderProduct;
use Illuminate\Http\JsonResponse;

class UpdateStatusController extends Controller
{
    /**
     * Mark a dispatch job (Delivery or Return) as Completed.
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(UpdateStatusRequest $request)
    {
        $validated = $request->validated();

        try {
            $user = auth('api_user')->user();

            $job = OrderProduct::with('order')
                ->whereHas('order')
                ->where('unique_id', $validated['order_product_unique_id'])
                ->firstOrFail();

            // Queue Line staging is informational, not restrictive (2026-07-23):
            // dispatch marking a delivery job Completed is never blocked for
            // lacking staging/fuel verification — a never-staged completion is a
            // Fast Track, recorded and surfaced, never prevented. Completion is
            // recorded by SyncOnScheduleUpdate off the event dispatched below.

            if ($validated['schedule_type'] === 'Delivery') {
                $job->delivery_status = $validated['schedule_status'];
            } else {
                $job->pickup_status = $validated['schedule_status'];
            }
            $job->save();

            $field = $validated['schedule_type'] === 'Delivery' ? 'delivery_status' : 'pickup_status';
            event(new OrderProductScheduleUpdated($job->order, $user, [
                'requested_data' => [
                    'type'  => strtolower($validated['schedule_type']),
                    $field  => $validated['schedule_status'],
                ],
                'order_product' => $job->toArray(),
            ]));
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update dispatch status.',
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
        ]);
    }
}
