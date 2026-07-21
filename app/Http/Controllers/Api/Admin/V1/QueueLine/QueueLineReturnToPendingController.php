<?php

namespace App\Http\Controllers\Api\Admin\V1\QueueLine;

use App\Http\Controllers\Api\Admin\V1\QueueLine\Concerns\RespondsWithQueueLineEnvelope;
use App\Http\Controllers\Controller;
use App\Models\Orders\QueueLineFuelVerification;
use App\Services\QueueLine\QueueLineOperationException;
use App\Services\QueueLine\QueueLineStagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST queue-line/{order_product_unique_id}/return-to-pending
 *
 * The counterpart of mark-staged — returns the item entirely to its
 * starting condition: staged latch cleared, the current fuel and key
 * records reversed with a recorded reason (append-only; history is never
 * deleted). Both must be confirmed again before the machine can be staged
 * — staging is all-or-nothing in BOTH directions.
 *
 * Idempotent by nature: repeating the call on an already-Pending item
 * reverses nothing further and succeeds.
 *
 * Queue Line Return To Pending
 * @group Admin App
 * @authenticated
 */
class QueueLineReturnToPendingController extends Controller
{
    use RespondsWithQueueLineEnvelope;

    public function __invoke(Request $request, string $orderProductUniqueId): JsonResponse
    {
        $validated = $request->validate([
            'performed_by' => ['required', 'string', 'max:255'], // user unique_id
        ]);

        $orderProduct = $this->findQueueItem($orderProductUniqueId);

        if (! $orderProduct) {
            return $this->itemNotFound();
        }

        $performedBy = $this->findActiveEmployee($validated['performed_by']);

        if (! $performedBy) {
            return $this->invalidEmployee();
        }

        try {
            QueueLineStagingService::returnToPending(
                orderProduct: $orderProduct,
                actor: auth('api_user')->user(),
                performedBy: $performedBy,
                source: QueueLineFuelVerification::SOURCE_MOBILE,
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
                'fully_staged' => false,
            ],
            message: 'Returned to Pending — fuel and key must be confirmed again before staging.',
        );
    }
}
