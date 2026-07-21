<?php

namespace App\Http\Controllers\Api\Admin\V1\QueueLine;

use App\Http\Controllers\Api\Admin\V1\QueueLine\Concerns\RespondsWithQueueLineEnvelope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\V1\QueueLine\QueueLineSwitchEquipmentRequest;
use App\Models\MaintenanceManagement\Equipment;
use App\Services\Equipment\EquipmentReassignmentService;
use App\Services\QueueLine\QueueFuelVerificationService;
use App\Services\QueueLine\QueueLineOperationException;
use Illuminate\Http\JsonResponse;

/**
 * POST queue-line/{order_product_unique_id}/switch-equipment
 *
 * Thin adapter over the SAME canonical EquipmentReassignmentService the web
 * board uses — no reassignment rule lives here. Source is ALWAYS mobile.
 * Conflicts never block; the technician receives a neutral notice for
 * office review. Replay safety: switching to the already-assigned unit is
 * the canonical no-op, reported as replayed (the idempotency token is
 * accepted for client bookkeeping; the operation is naturally idempotent).
 *
 * Queue Line Switch Equipment
 * @group Admin App
 * @authenticated
 */
class QueueLineSwitchEquipmentController extends Controller
{
    use RespondsWithQueueLineEnvelope;

    public function __invoke(QueueLineSwitchEquipmentRequest $request, string $orderProductUniqueId): JsonResponse
    {
        $validated = $request->validated();

        $orderProduct = $this->findQueueItem($orderProductUniqueId);

        if (! $orderProduct) {
            return $this->itemNotFound();
        }

        $replacement = Equipment::where('unique_id', $validated['equipment_unique_id'])->first();

        if (! $replacement) {
            return $this->fail('QUEUE_EQUIPMENT_NOT_FOUND', 'That equipment could not be found.', JsonResponse::HTTP_NOT_FOUND);
        }

        $performedBy = $this->findActiveEmployee($validated['performed_by']);

        if (! $performedBy) {
            return $this->invalidEmployee();
        }

        try {
            $result = EquipmentReassignmentService::switch(
                orderProduct: $orderProduct,
                replacement: $replacement,
                performedBy: $performedBy,
                actor: auth('api_user')->user(),
                source: EquipmentReassignmentService::SOURCE_MOBILE,
                reason: $validated['reason'] ?? null,
            );
        } catch (QueueLineOperationException $e) {
            return $this->fail(
                $e->errorCode,
                $e->getMessage(),
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                currentEquipment: $orderProduct->softAssignment?->equipment,
            );
        }

        $fresh = $orderProduct->fresh(['softAssignment.equipment']);
        $fuelCurrent = QueueFuelVerificationService::currentVerification($fresh);

        return $this->ok(
            [
                'order_product_unique_id' => $orderProduct->unique_id,
                'ordered_product' => $orderProduct->product_name,
                'previous_equipment' => $result['previous'] ? [
                    'unique_id' => $result['previous']->unique_id,
                    'display_id' => $result['previous']->equipment_id,
                    'name' => $result['previous']->equipment_name,
                ] : null,
                'current_equipment' => [
                    'unique_id' => $result['replacement']->unique_id,
                    'display_id' => $result['replacement']->equipment_id,
                    'name' => $result['replacement']->equipment_name,
                ],
                'classification' => $result['classification'],
                'replayed' => ! $result['changed'],
                'conflict_count' => $result['conflicts']->count(),
                'conflict_notice' => $result['conflicts']->isNotEmpty()
                    ? 'Equipment switched successfully. A scheduling conflict was flagged for office review.'
                    : null,
                // A fresh episode is unverified — the app should prompt for
                // Fuel Full before the release workflows will accept the unit
                'fuel_state' => $fuelCurrent ? 'verified' : 'not_verified',
            ],
            message: $result['changed']
                ? "Equipment switched to {$result['replacement']->equipment_name}."
                : "{$result['replacement']->equipment_name} is already assigned to this item.",
        );
    }
}
