<?php

namespace App\Http\Controllers\Api\Admin\V1\QueueLine;

use App\Http\Controllers\Api\Admin\V1\QueueLine\Concerns\RespondsWithQueueLineEnvelope;
use App\Http\Controllers\Controller;
use App\Models\Stores\Store;
use App\Services\QueueLine\QueueLineMobilePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET queue-line — the standalone mobile board.
 *
 * The server owns eligibility, ordering (Rush → Overdue → Today → Tomorrow),
 * assignment classification, fuel currency, and the allowed action set; the
 * app only renders. Optional ?store={store unique_id} filters by outbound
 * store (note: this application has no per-user store restriction model —
 * the filter is a view scope, and an invalid store id is rejected).
 *
 * @group Admin App
 * @authenticated
 */
class QueueLineBoardController extends Controller
{
    use RespondsWithQueueLineEnvelope;

    public function __invoke(Request $request): JsonResponse
    {
        $storeId = null;

        if ($storeUniqueId = $request->query('store')) {
            $store = Store::active()->where('unique_id', $storeUniqueId)->first();

            if (! $store) {
                return $this->fail(
                    'QUEUE_STORE_INVALID',
                    'That store could not be found.',
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                    'Pick a store from the store list.',
                );
            }

            $storeId = $store->id;
        }

        $board = QueueLineMobilePresenter::board($storeId);

        return $this->ok(
            ['items' => $board['items']],
            $board['meta'] + ['store_filter' => $storeUniqueId],
        );
    }
}
