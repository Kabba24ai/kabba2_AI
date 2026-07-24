<?php

namespace App\Http\Controllers\Api\Admin\V1\QueueLine;

use App\Http\Controllers\Api\Admin\V1\QueueLine\Concerns\RespondsWithQueueLineEnvelope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\V1\QueueLine\QueueLineMarkStagedRequest;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\QueueLineFuelVerification;
use App\Services\QueueLine\QueueLineOperationException;
use App\Services\QueueLine\QueueLineStagingService;
use Illuminate\Http\JsonResponse;

/**
 * Queue Line Mark Staged
 *
 * POST queue-line/{order_product_unique_id}/mark-staged
 *
 * The ONE staging action — identical to the web thumbs-up modal: one
 * atomic QueueLineStagingService::markStaged() call records the canonical
 * fuel verification, the key confirmation, and the staged latch together.
 * Staging is deliberately all-or-nothing: there is no fuel-only or
 * key-only step. fuel_full and key_with_machine must both be true or the
 * whole request is rejected (QUEUE_FUEL_NOT_FULL / QUEUE_KEY_MISSING) and
 * nothing is recorded.
 *
 * Stale screens get QUEUE_ASSIGNMENT_CHANGED with the currently assigned
 * unit; the idempotency token replays the original event safely.
 *
 */
class QueueLineMarkStagedController extends Controller
{
    use RespondsWithQueueLineEnvelope;

    /**
     * Queue Line Mark Staged
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(QueueLineMarkStagedRequest $request, string $orderProductUniqueId): JsonResponse
    {
        $validated = $request->validated();

        $orderProduct = $this->findQueueItem($orderProductUniqueId);

        if (! $orderProduct) {
            return $this->itemNotFound();
        }

        $equipment = Equipment::where('unique_id', $validated['equipment_unique_id'])->first();

        if (! $equipment) {
            return $this->fail('QUEUE_EQUIPMENT_NOT_FOUND', 'That equipment could not be found.', JsonResponse::HTTP_NOT_FOUND);
        }

        $performedBy = $this->findActiveEmployee($validated['performed_by']);

        if (! $performedBy) {
            return $this->invalidEmployee();
        }

        try {
            $result = QueueLineStagingService::markStaged(
                orderProduct: $orderProduct,
                expected: $equipment,
                performedBy: $performedBy,
                actor: auth('api_user')->user(),
                // Omitted/null when the payload marked the check
                // not_applicable — the service ignores it for such units
                fuelFull: (bool) ($validated['fuel_full'] ?? false),
                keyWithMachine: (bool) ($validated['key_with_machine'] ?? false),
                source: QueueLineFuelVerification::SOURCE_MOBILE,
                idempotencyToken: $validated['idempotency_token'] ?? null,
            );
        } catch (QueueLineOperationException $e) {
            return $this->fail(
                $e->errorCode,
                $e->getMessage(),
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                currentEquipment: $orderProduct->softAssignment?->equipment,
            );
        }

        return $this->ok(
            [
                'order_product_unique_id' => $orderProduct->unique_id,
                'current_equipment' => [
                    'unique_id' => $equipment->unique_id,
                    'display_id' => $equipment->equipment_id,
                    'name' => $equipment->equipment_name,
                    'power_source_type' => $equipment->power_source_type,
                    'key_starting_mechanism' => $equipment->key_starting_mechanism,
                    'is_fuel' => $equipment->hasFuelData(),
                    'is_key' => $equipment->hasKeyData(),
                ],
                'fully_staged' => true,
                'staged_by' => $performedBy->full_name,
                'replayed' => $result['replayed'],
            ],
            message: $result['replayed']
                ? 'This machine is already staged.'
                : "{$equipment->equipment_name} marked as staged — ready for handoff.",
        );
    }
}
