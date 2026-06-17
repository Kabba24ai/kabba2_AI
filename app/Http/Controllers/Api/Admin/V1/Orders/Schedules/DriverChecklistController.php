<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\Schedules;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\V1\Orders\Schedules\DriverChecklistRequest;
use App\Events\Admin\Orders\OrderProductDriverChecklistUpdated;
use App\Models\Orders\OrderProduct;
use Illuminate\Http\JsonResponse;

class DriverChecklistController extends Controller
{
    /**
     * Update Driver Checklist
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(DriverChecklistRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $user = auth('api_user')->user();

            $schedule = OrderProduct::with('order')
                    ->whereHas('order')
                    ->where('unique_id', $validated['order_product_unique_id'])
                    ->firstOrFail();

            $fields = array_filter([
                'equipment_fuel'          => $validated['equipment_fuel'] ?? null,
                'equipment_key_location'  => $validated['equipment_key_location'] ?? null,
                'equipment_driver_status' => $validated['equipment_driver_status'] ?? null,
            ], fn($v) => !is_null($v));

            $schedule->fill($fields);
            $schedule->save();

            $schedule->order->makeHidden([
                'terms_collection',
                'pending_terms_content',
                'accepted_terms_content',
            ]);

            $data = [
                'requested_data' => $fields,
                'order_product' => $schedule->toArray(),
            ];

            // Fire an event for the updated schedule
            event(new OrderProductDriverChecklistUpdated(
                $schedule->order,
                $user,
                $data
            ));
        } catch (\Throwable $th) {

            return response()->json([
                'status'  => false,
                'message' => 'Failed to update driver checklist.',
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Driver checklist updated successfully.',
        ]);
    }
}
