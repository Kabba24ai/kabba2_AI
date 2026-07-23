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
            //
            // Queue Line staging is now INFORMATIONAL, not restrictive (2026-07-23,
            // approved): the release guard no longer blocks a driver from leaving
            // the yard with an un-staged / un-fuel-verified machine. A machine that
            // departs without going through staging is a legitimate Fast Track — it
            // is recorded (queue_line_items.staged_at stays null against a completion
            // latch) and surfaced with a "NOT STAGED / FAST TRACK" badge, never
            // prevented. See QueueLineReleaseGuard (now an informational evaluator).
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
                    // PREP only — fuel/keys/attachments. The truck has NOT left
                    // the yard yet, so this no longer completes the Queue Line item.
                    $statusDrivenFields = [
                        $typePrefix . '_ready_to_go_at' => now(),
                        $typePrefix . '_is_arrived' => false,
                    ];
                }

                if ($status === EquipmentDriverStatus::ON_MY_WAY->value) {
                    // DEPARTURE — "Load Map & Go". This is the actual moment the
                    // equipment leaves the yard and is the Queue Line completion
                    // trigger (CompleteOnDispatchStart listens for it on the
                    // delivery leg). Stamp a dedicated timestamp for the audit trail.
                    $statusDrivenFields = [
                        $typePrefix . '_on_my_way_at' => now(),
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
