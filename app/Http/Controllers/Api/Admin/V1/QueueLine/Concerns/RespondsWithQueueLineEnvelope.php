<?php

namespace App\Http\Controllers\Api\Admin\V1\QueueLine\Concerns;

use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\OrderProduct;
use Illuminate\Http\JsonResponse;

/**
 * The ONE response envelope for the standalone Queue Line mobile API (§5).
 *
 * Success:  { success: true,  data: {...}, meta: {...} }
 * Failure:  { success: false, error: { code, message, corrective_action,
 *             current_equipment? } }  — plus a top-level message for older
 *             generic handlers.
 *
 * Every failure carries a stable machine code, an employee-facing message,
 * and a corrective action. Never internal ids, never stack traces, never
 * implementation terms.
 */
trait RespondsWithQueueLineEnvelope
{
    protected function ok(array $data, array $meta = [], ?string $message = null): JsonResponse
    {
        return response()->json(array_filter([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta ?: null,
        ], fn ($v) => $v !== null));
    }

    protected function fail(
        string $code,
        string $message,
        int $status = JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
        ?string $correctiveAction = null,
        ?Equipment $currentEquipment = null,
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => array_filter([
                'code' => $code,
                'message' => $message,
                'corrective_action' => $correctiveAction ?? self::defaultCorrectiveAction($code),
                'current_equipment' => $currentEquipment ? [
                    'unique_id' => $currentEquipment->unique_id,
                    'display_id' => $currentEquipment->equipment_id,
                    'name' => $currentEquipment->equipment_name,
                ] : null,
            ], fn ($v) => $v !== null),
        ], $status);
    }

    /** Route-model resolution by unique_id ONLY (no internal ids on the wire). */
    protected function findQueueItem(string $orderProductUniqueId): ?OrderProduct
    {
        return OrderProduct::with([
            'softAssignment.equipment.assignedProduct:id,product_name',
            'softAssignment.equipment.activeEquipmentRentalReadyTemplate',
            'order.lastPayment',
            'product:id,unique_id,product_name',
            'product.mediaChildren',
            'deliveryStore:id,unique_id,store_name',
            'queueLineItem',
        ])
            ->whereHas('order')
            ->where('unique_id', $orderProductUniqueId)
            ->first();
    }

    protected function itemNotFound(): JsonResponse
    {
        return $this->fail(
            'QUEUE_ITEM_NOT_FOUND',
            'That order item could not be found.',
            JsonResponse::HTTP_NOT_FOUND,
            'Refresh the Queue Line board and try again.',
        );
    }

    protected function findActiveEmployee(string $uniqueId): ?User
    {
        return User::active()->where('unique_id', $uniqueId)->first();
    }

    protected function invalidEmployee(): JsonResponse
    {
        return $this->fail(
            'QUEUE_EMPLOYEE_INVALID',
            'Select an active employee.',
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
            'Choose your name from the employee list, then retry.',
        );
    }

    private static function defaultCorrectiveAction(string $code): string
    {
        return match ($code) {
            'QUEUE_EQUIPMENT_REQUIRED' => 'Assign a machine to this item on the Queue Line first.',
            'QUEUE_EQUIPMENT_NOT_FOUND' => 'Re-scan or re-search the machine and try again.',
            'QUEUE_EQUIPMENT_INACTIVE' => 'Choose a different machine — that record is no longer active.',
            'QUEUE_EQUIPMENT_RENTED' => 'Choose a different machine — that one is out with a customer.',
            'QUEUE_REASON_REQUIRED' => 'Add a short reason for staging a non-matching machine, then retry.',
            'QUEUE_ASSIGNMENT_CHANGED' => 'Refresh the item and act on the machine currently assigned.',
            'QUEUE_ITEM_NOT_ELIGIBLE' => 'This item is no longer on the Queue Line — refresh the board.',
            'QUEUE_ITEM_DELIVERED' => 'This equipment has already been delivered — no Queue Line action applies.',
            'QUEUE_ITEM_ALREADY_COMPLETED' => 'This item already left the Queue Line — refresh the board.',
            'QUEUE_FUEL_VERIFICATION_REQUIRED' => 'Verify Fuel Full for the assigned machine on the Queue Line.',
            'QUEUE_ITEM_SUPPRESSED' => 'This item was removed from Queue Line management.',
            default => 'Refresh the Queue Line board and try again.',
        };
    }
}
