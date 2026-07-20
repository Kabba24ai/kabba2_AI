<?php

namespace App\Http\Controllers\Api\Admin\V1\QueueLine;

use App\Http\Controllers\Api\Admin\V1\QueueLine\Concerns\RespondsWithQueueLineEnvelope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\V1\QueueLine\QueueLineVerifyFuelRequest;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\QueueLineFuelVerification;
use App\Services\QueueLine\QueueFuelVerificationService;
use App\Services\QueueLine\QueueLineOperationException;
use Illuminate\Http\JsonResponse;

/**
 * POST queue-line/{order_product_unique_id}/verify-fuel
 *
 * "Verify Fuel Full" — thin adapter over the SAME canonical
 * QueueFuelVerificationService the web board uses. Source is ALWAYS
 * queue_line_mobile. The sign-off binds to the exact assignment EPISODE: a
 * stale screen gets QUEUE_ASSIGNMENT_CHANGED with the currently assigned
 * unit; the idempotency token replays the original event safely, even after
 * the assignment changed.
 *
 * Reversal is deliberately NOT exposed to mobile (approved policy):
 * corrections are supervised web actions with reason + append-only history.
 *
 * @group Admin App
 * @authenticated
 */
class QueueLineVerifyFuelController extends Controller
{
    use RespondsWithQueueLineEnvelope;

    public function __invoke(QueueLineVerifyFuelRequest $request, string $orderProductUniqueId): JsonResponse
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
            $result = QueueFuelVerificationService::verify(
                orderProduct: $orderProduct,
                expected: $equipment,
                performedBy: $performedBy,
                actor: auth('api_user')->user(),
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

        $verification = $result['verification'];

        return $this->ok(
            [
                'order_product_unique_id' => $orderProduct->unique_id,
                'current_equipment' => [
                    'unique_id' => $verification->equipment?->unique_id ?? $equipment->unique_id,
                    'display_id' => $equipment->equipment_id,
                    'name' => $equipment->equipment_name,
                ],
                'fuel_state' => 'verified',
                'verified_at' => $verification->created_at->toIso8601String(),
                'verified_by' => $verification->performedBy?->full_name
                    ?? User::find($verification->performed_by)?->full_name,
                'replayed' => $result['replayed'],
            ],
            message: $result['replayed']
                ? 'Fuel verification already recorded for this machine.'
                : "Fuel Full verified for {$equipment->equipment_name}.",
        );
    }
}
