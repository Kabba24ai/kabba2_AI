<?php

namespace App\Http\Controllers\Api\Admin\V1\QueueLine;

use App\Http\Controllers\Api\Admin\V1\QueueLine\Concerns\RespondsWithQueueLineEnvelope;
use App\Http\Controllers\Controller;
use App\Services\QueueLine\QueueLineMobilePresenter;
use Illuminate\Http\JsonResponse;

/**
 * Queue Line Item Detail
 *
 * GET queue-line/{order_product_unique_id} — item detail: full operational
 * state (assignment, fuel, readiness, suppression, completion) for the
 * mobile detail screen. Read-only.
 *
 */
class QueueLineItemController extends Controller
{
    use RespondsWithQueueLineEnvelope;

    /**
     * Queue Line Item Detail
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(string $orderProductUniqueId): JsonResponse
    {
        $item = $this->findQueueItem($orderProductUniqueId);

        if (! $item) {
            return $this->itemNotFound();
        }

        return $this->ok(QueueLineMobilePresenter::item($item));
    }
}
