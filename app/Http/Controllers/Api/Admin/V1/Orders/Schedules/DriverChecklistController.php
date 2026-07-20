<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\Schedules;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\V1\Orders\Schedules\DriverChecklistRequest;
use App\Events\Admin\Orders\OrderProductDriverChecklistUpdated;
use App\Enums\Orders\EquipmentDriverStatus;
use App\Models\Orders\OrderProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            // Wrapped in a transaction (including the event() dispatch below, since its
            // listener runs synchronously inline) so a failure anywhere in this sequence
            // rolls back the schedule update instead of leaving a partial commit behind
            // the generic failure response below.
            // Queue Line release enforcement (Phase 3C): "Ready to Go" on the
            // DELIVERY leg is the moment equipment leaves the yard — for a
            // Queue Line-managed item the currently staged unit must carry a
            // CURRENT Fuel Full verification. Non-queue items (returns,
            // retail, out-of-window, Remove Forever, already completed) are
            // never touched. Checked BEFORE the transaction so a block never
            // writes anything.
            if (
                $validated['checklist_type'] === 'delivery'
                && ($validated['equipment_driver_status'] ?? null) === \App\Enums\Orders\EquipmentDriverStatus::READY_TO_GO->value
            ) {
                $guardTarget = OrderProduct::with('softAssignment.equipment', 'queueLineItem')
                    ->where('unique_id', $validated['order_product_unique_id'])
                    ->first();

                if ($guardTarget && ($blocked = \App\Services\QueueLine\QueueLineReleaseGuard::check($guardTarget))) {
                    return response()->json([
                        'success' => false,
                        'message' => $blocked['message'],
                        'error' => $blocked,
                    ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
                }
            }

            DB::transaction(function () use ($validated) {
                $user = auth('api_user')->user();

                $schedule = OrderProduct::with('order')
                        ->whereHas('order')
                        ->where('unique_id', $validated['order_product_unique_id'])
                        ->firstOrFail();

                $typePrefix = $validated['checklist_type'];
                $status = $validated['equipment_driver_status'] ?? null;
                $statusDrivenFields = [];

                if ($status === EquipmentDriverStatus::READY_TO_GO->value) {
                    $statusDrivenFields = [
                        $typePrefix . '_ready_to_go_at' => now(),
                        $typePrefix . '_is_arrived' => false,
                    ];
                }

                if ($status === EquipmentDriverStatus::ARRIVED->value) {
                    $statusDrivenFields = [
                        $typePrefix . '_arrived_at' => now(),
                        $typePrefix . '_is_arrived' => true,
                        $typePrefix . '_is_delivered' => true,
                    ];
                }

                $fields = array_filter([
                    $typePrefix . '_equipment_fuel'          => $validated['equipment_fuel'] ?? null,
                    $typePrefix . '_equipment_key_location'  => $validated['equipment_key_location'] ?? null,
                    $typePrefix . '_equipment_driver_status' => $validated['equipment_driver_status'] ?? null,
                ], fn($v) => !is_null($v));

                $fields = array_merge($fields, $statusDrivenFields);

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
            });
        } catch (\Throwable $th) {
            Log::channel('equipment_status')->error('DriverChecklistController failed', [
                'order_product_unique_id' => $validated['order_product_unique_id'] ?? null,
                'error'                   => $th->getMessage(),
                'exception'               => get_class($th),
            ]);

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
