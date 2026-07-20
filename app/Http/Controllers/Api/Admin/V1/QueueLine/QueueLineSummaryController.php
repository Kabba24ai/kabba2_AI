<?php

namespace App\Http\Controllers\Api\Admin\V1\QueueLine;

use App\Http\Controllers\Api\Admin\V1\QueueLine\Concerns\RespondsWithQueueLineEnvelope;
use App\Http\Controllers\Controller;
use App\Models\Stores\Store;
use App\Services\QueueLine\QueueLineMobilePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET queue-line/summary — lightweight counts for the home-page badge (§15).
 * Never returns item payloads; the app must not load the board for a badge.
 *
 * @group Admin App
 * @authenticated
 */
class QueueLineSummaryController extends Controller
{
    use RespondsWithQueueLineEnvelope;

    public function __invoke(Request $request): JsonResponse
    {
        $storeId = null;

        if ($storeUniqueId = $request->query('store')) {
            $storeId = Store::active()->where('unique_id', $storeUniqueId)->value('id');
        }

        return $this->ok(['counts' => QueueLineMobilePresenter::summary($storeId)]);
    }
}
