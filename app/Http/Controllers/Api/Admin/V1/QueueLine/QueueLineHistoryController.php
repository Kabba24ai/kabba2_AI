<?php

namespace App\Http\Controllers\Api\Admin\V1\QueueLine;

use App\Http\Controllers\Api\Admin\V1\QueueLine\Concerns\RespondsWithQueueLineEnvelope;
use App\Http\Controllers\Controller;
use App\Services\QueueLine\QueueLineHistory;
use Illuminate\Http\JsonResponse;

/**
 * Queue Line History
 *
 * GET queue-line/{order_product_unique_id}/history
 *
 * The combined operational timeline (assignment, fuel, release, reversal,
 * admin) — the SAME read-only projection the web history drawer renders
 * (QueueLineHistory). The app must never assemble history from other
 * endpoints. Prior-assignment fuel events carry current_episode=false so
 * they can never be mistaken for the current verification.
 *
 */
class QueueLineHistoryController extends Controller
{
    use RespondsWithQueueLineEnvelope;

    /**
     * Queue Line History
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

        $events = QueueLineHistory::timeline($item)->map(fn (array $event) => [
            'type' => $event['type'],
            'title' => $event['title'],
            'detail' => $event['detail'],
            'employee' => $event['employee'],
            'actor' => $event['actor'],
            'equipment_display_id' => $event['equipment'],
            'source' => $event['source'] ?? null,
            'current_episode' => $event['current_episode'] ?? null,
            // order_histories.action_date is an uncast string — normalize
            'at' => $event['at'] ? \Illuminate\Support\Carbon::parse($event['at'])->toIso8601String() : null,
        ])->values()->all();

        return $this->ok([
            'order_product_unique_id' => $item->unique_id,
            'events' => $events,
        ]);
    }
}
