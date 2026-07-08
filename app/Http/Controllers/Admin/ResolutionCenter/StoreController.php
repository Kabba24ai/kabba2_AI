<?php

namespace App\Http\Controllers\Admin\ResolutionCenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolutionCenter\StoreCaseRequest;
use App\Models\Orders\Order;
use App\Services\ResolutionCenterService;
use Illuminate\Support\Facades\Log;

/**
 * Phase 3.3 — Customer Resolution Center Foundation.
 *
 * Started from the Order Edit screen (where the customer and order are
 * already known) — no separate customer/order search screen exists, since
 * that would duplicate lookup UI this codebase already has elsewhere.
 */
class StoreController extends Controller
{
    public function __invoke(string $orderUniqueId, StoreCaseRequest $request)
    {
        $validated = $request->validated();

        try {
            $order = Order::where('unique_id', $orderUniqueId)->firstOrFail();

            $case = ResolutionCenterService::startCase(
                customerId: $order->customer_id,
                orderId: $order->id,
                issue: $validated['issue'],
                responsibleUserId: auth()->id(),
                scenarioKey: $validated['scenario_key'],
            );

            flash('Resolution case opened.')->success();

            return redirect()->route('admin.resolution-center.show', $case->unique_id);
        } catch (\Throwable $e) {
            report($e);
            Log::error('Resolution Center case creation error for order '.$orderUniqueId.': '.$e->getMessage());

            flash('Something went wrong while opening the resolution case.')->error();

            return redirect()->back();
        }
    }
}
